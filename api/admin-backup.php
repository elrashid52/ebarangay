<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// Check if admin is logged in
if(!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch($action) {
    case 'create_backup':
        try {
            $backupType = $_POST['backup_type'] ?? 'full'; // 'database', 'files', 'full'
            $backupName = trim($_POST['backup_name'] ?? '');
            if (empty($backupName)) {
                $backupName = 'backup_' . date('Y-m-d_H-i-s');
            }
            
            // Sanitize backup name
            $backupName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $backupName);
            
            $backupDir = '../backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            // Check if backup directory is writable
            if (!is_writable($backupDir)) {
                throw new Exception('Backup directory is not writable');
            }
            
            $result = [];
            $totalSize = 0;
            
            // Database backup
            if ($backupType === 'database' || $backupType === 'full') {
                $dbBackupFile = $backupDir . $backupName . '_database.sql';
                $dbResult = createDatabaseBackup($db, $dbBackupFile);
                $result['database'] = $dbResult;
                if ($dbResult['success'] && isset($dbResult['size'])) {
                    $totalSize += $dbResult['size'];
                }
            }
            
            // Files backup
            if ($backupType === 'files' || $backupType === 'full') {
                $filesBackupFile = $backupDir . $backupName . '_files.zip';
                $filesResult = createFilesBackup($filesBackupFile);
                $result['files'] = $filesResult;
                if ($filesResult['success'] && isset($filesResult['size'])) {
                    $totalSize += $filesResult['size'];
                }
            }
            
            // Log backup activity
            logBackupActivity($_SESSION['admin_id'], 'create_backup', $backupType, $backupName);
            
            // Check if any backup failed
            $hasFailures = false;
            $errorMessages = [];
            
            foreach ($result as $type => $res) {
                if (!$res['success']) {
                    $hasFailures = true;
                    $errorMessages[] = "$type: " . $res['message'];
                }
            }
            
            if ($hasFailures) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Backup partially failed: ' . implode(', ', $errorMessages),
                    'result' => $result
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Backup created successfully',
                    'backup_name' => $backupName,
                    'total_size' => $totalSize,
                    'result' => $result
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Backup creation error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
        }
        break;
        
    case 'get_backup_stats':
        try {
            $backupDir = '../backups/';
            $stats = [
                'total_backups' => 0,
                'total_size' => 0,
                'latest_backup' => 'Never',
                'status' => 'Ready'
            ];
            
            if (is_dir($backupDir)) {
                $files = scandir($backupDir);
                $backupGroups = [];
                $latestTime = 0;
                
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && !is_dir($backupDir . $file)) {
                        $fileInfo = pathinfo($file);
                        $baseName = preg_replace('/_(database|files)$/', '', $fileInfo['filename']);
                        
                        if (!isset($backupGroups[$baseName])) {
                            $backupGroups[$baseName] = true;
                            $stats['total_backups']++;
                        }
                        
                        $fileSize = filesize($backupDir . $file);
                        $stats['total_size'] += $fileSize;
                        
                        $fileTime = filemtime($backupDir . $file);
                        if ($fileTime > $latestTime) {
                            $latestTime = $fileTime;
                            $stats['latest_backup'] = date('M j, Y H:i', $fileTime);
                        }
                    }
                }
                
                // Format total size
                $stats['total_size_formatted'] = formatBytes($stats['total_size']);
            }
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to get stats: ' . $e->getMessage()]);
        }
        break;
        
    case 'list_backups':
        try {
            $backupDir = '../backups/';
            $backups = [];
            
            if (is_dir($backupDir)) {
                $files = scandir($backupDir);
                $backupGroups = [];
                
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && !is_dir($backupDir . $file)) {
                        $fileInfo = pathinfo($file);
                        $baseName = preg_replace('/_(database|files)$/', '', $fileInfo['filename']);
                        
                        if (!isset($backupGroups[$baseName])) {
                            $backupGroups[$baseName] = [
                                'name' => $baseName,
                                'created_at' => date('Y-m-d H:i:s', filemtime($backupDir . $file)),
                                'database' => false,
                                'files' => false,
                                'size' => 0
                            ];
                        }
                        
                        if (strpos($file, '_database.sql') !== false) {
                            $backupGroups[$baseName]['database'] = true;
                            $backupGroups[$baseName]['database_file'] = $file;
                            $backupGroups[$baseName]['database_size'] = filesize($backupDir . $file);
                        } elseif (strpos($file, '_files.zip') !== false) {
                            $backupGroups[$baseName]['files'] = true;
                            $backupGroups[$baseName]['files_file'] = $file;
                            $backupGroups[$baseName]['files_size'] = filesize($backupDir . $file);
                        }
                        
                        $backupGroups[$baseName]['size'] += filesize($backupDir . $file);
                    }
                }
                
                $backups = array_values($backupGroups);
                usort($backups, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                
                // Format sizes
                foreach ($backups as &$backup) {
                    $backup['size_formatted'] = formatBytes($backup['size']);
                }
            }
            
            echo json_encode(['success' => true, 'backups' => $backups]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to list backups: ' . $e->getMessage()]);
        }
        break;
        
    case 'restore_backup':
        try {
            $backupName = $_POST['backup_name'] ?? '';
            $restoreType = $_POST['restore_type'] ?? 'full';
            
            if (empty($backupName)) {
                echo json_encode(['success' => false, 'message' => 'Backup name is required']);
                exit;
            }
            
            $backupDir = '../backups/';
            $result = [];
            
            // Database restore
            if ($restoreType === 'database' || $restoreType === 'full') {
                $dbBackupFile = $backupDir . $backupName . '_database.sql';
                if (file_exists($dbBackupFile)) {
                    $dbResult = restoreDatabaseBackup($db, $dbBackupFile);
                    $result['database'] = $dbResult;
                } else {
                    $result['database'] = ['success' => false, 'message' => 'Database backup file not found'];
                }
            }
            
            // Files restore
            if ($restoreType === 'files' || $restoreType === 'full') {
                $filesBackupFile = $backupDir . $backupName . '_files.zip';
                if (file_exists($filesBackupFile)) {
                    $filesResult = restoreFilesBackup($filesBackupFile);
                    $result['files'] = $filesResult;
                } else {
                    $result['files'] = ['success' => false, 'message' => 'Files backup file not found'];
                }
            }
            
            // Log restore activity
            logBackupActivity($_SESSION['admin_id'], 'restore_backup', $restoreType, $backupName);
            
            // Check if any restore failed
            $hasFailures = false;
            $errorMessages = [];
            $successMessages = [];
            
            foreach ($result as $type => $res) {
                if (!$res['success']) {
                    $hasFailures = true;
                    $errorMessages[] = "$type: " . $res['message'];
                } else {
                    $successMessages[] = "$type restored successfully";
                }
            }
            
            $message = implode(', ', $successMessages);
            if ($hasFailures) {
                $message .= '. Errors: ' . implode(', ', $errorMessages);
            }
            
            echo json_encode([
                'success' => !$hasFailures, 
                'message' => $message,
                'result' => $result
            ]);
            
        } catch (Exception $e) {
            error_log("Restore error: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Restore failed: ' . $e->getMessage()]);
        }
        break;
        
    case 'delete_backup':
        try {
            $backupName = $_POST['backup_name'] ?? '';
            
            if (empty($backupName)) {
                echo json_encode(['success' => false, 'message' => 'Backup name is required']);
                exit;
            }
            
            $backupDir = '../backups/';
            $deleted = [];
            
            // Delete database backup
            $dbFile = $backupDir . $backupName . '_database.sql';
            if (file_exists($dbFile)) {
                unlink($dbFile);
                $deleted[] = 'database';
            }
            
            // Delete files backup
            $filesFile = $backupDir . $backupName . '_files.zip';
            if (file_exists($filesFile)) {
                unlink($filesFile);
                $deleted[] = 'files';
            }
            
            // Log delete activity
            logBackupActivity($_SESSION['admin_id'], 'delete_backup', implode(',', $deleted), $backupName);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Backup deleted successfully',
                'deleted' => $deleted
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()]);
        }
        break;
        
    case 'download_backup':
        $backupName = $_GET['backup_name'] ?? '';
        $backupType = $_GET['backup_type'] ?? 'database';
        
        if (empty($backupName)) {
            echo json_encode(['success' => false, 'message' => 'Backup name is required']);
            exit;
        }
        
        $backupDir = '../backups/';
        $extension = ($backupType === 'files') ? '.zip' : '.sql';
        $filename = $backupName . '_' . $backupType . $extension;
        $filepath = $backupDir . $filename;
        
        if (file_exists($filepath)) {
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Length: ' . filesize($filepath));
            readfile($filepath);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Backup file not found']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper functions
function createDatabaseBackup($db, $backupFile) {
    try {
        // Set longer execution time for large databases
        set_time_limit(300); // 5 minutes
        
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        if (empty($tables)) {
            throw new Exception('No tables found in database');
        }
        
        $sql = "-- E-Barangay Portal Database Backup\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
            try {
                // Get table structure
                $result = $db->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $row['Create Table'] . ";\n\n";
                
                // Get table data
                $result = $db->query("SELECT * FROM `$table`");
                if ($result->rowCount() > 0) {
                    $sql .= "INSERT INTO `$table` VALUES\n";
                    $rows = [];
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $values = array_map(function($value) use ($db) {
                            return $value === null ? 'NULL' : $db->quote($value);
                        }, array_values($row));
                        $rows[] = '(' . implode(', ', $values) . ')';
                    }
                    $sql .= implode(",\n", $rows) . ";\n\n";
                }
            } catch (Exception $e) {
                error_log("Error backing up table $table: " . $e->getMessage());
                // Continue with other tables
            }
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        $bytesWritten = file_put_contents($backupFile, $sql);
        
        if ($bytesWritten === false) {
            throw new Exception('Failed to write backup file');
        }
        
        return [
            'success' => true, 
            'message' => 'Database backup created',
            'file' => basename($backupFile),
            'size' => filesize($backupFile),
            'tables_count' => count($tables)
        ];
        
    } catch (Exception $e) {
        error_log("Database backup error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database backup failed: ' . $e->getMessage()];
    }
}

function createFilesBackup($backupFile) {
    try {
        // Set longer execution time for large file operations
        set_time_limit(600); // 10 minutes
        
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not available');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($backupFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($result !== TRUE) {
            throw new Exception('Cannot create zip file');
        }
        
        $fileCount = 0;
        
        // Add uploads directory
        $uploadsDir = '../uploads/';
        if (is_dir($uploadsDir)) {
            $fileCount += addDirectoryToZip($zip, $uploadsDir, 'uploads/');
        }
        
        // Add assets directory
        $assetsDir = '../assets/';
        if (is_dir($assetsDir)) {
            $fileCount += addDirectoryToZip($zip, $assetsDir, 'assets/');
        }
        
        // Add config files (excluding sensitive data)
        $configFiles = ['../config/database.php'];
        foreach ($configFiles as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, 'config/' . basename($file));
                $fileCount++;
            }
        }
        
        $closeResult = $zip->close();
        
        if (!$closeResult) {
            throw new Exception('Failed to close zip file');
        }
        
        if (!file_exists($backupFile)) {
            throw new Exception('Backup file was not created');
        }
        
        return [
            'success' => true, 
            'message' => 'Files backup created',
            'file' => basename($backupFile),
            'size' => filesize($backupFile),
            'files_count' => $fileCount
        ];
        
    } catch (Exception $e) {
        error_log("Files backup error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Files backup failed: ' . $e->getMessage()];
    }
}

function addDirectoryToZip($zip, $dir, $zipPath) {
    $fileCount = 0;
    
    try {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . substr($filePath, strlen(realpath($dir)) + 1);
                $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
                $fileCount++;
            }
        }
    } catch (Exception $e) {
        error_log("Error adding directory to zip: " . $e->getMessage());
    }
    
    return $fileCount;
}

function restoreDatabaseBackup($db, $backupFile) {
    try {
        // Set longer execution time
        set_time_limit(300); // 5 minutes
        
        if (!file_exists($backupFile)) {
            throw new Exception('Backup file does not exist');
        }
        
        $sql = file_get_contents($backupFile);
        
        if ($sql === false) {
            throw new Exception('Failed to read backup file');
        }
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        
        $db->beginTransaction();
        
        $executedStatements = 0;
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement) && !preg_match('/^\/\*/', $statement)) {
                try {
                    $db->exec($statement);
                    $executedStatements++;
                } catch (Exception $e) {
                    error_log("Error executing SQL statement: " . $e->getMessage());
                    // Continue with other statements for non-critical errors
                }
            }
        }
        
        $db->commit();
        
        return [
            'success' => true, 
            'message' => "Database restored successfully ($executedStatements statements executed)"
        ];
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Database restore error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database restore failed: ' . $e->getMessage()];
    }
}

function restoreFilesBackup($backupFile) {
    try {
        // Set longer execution time
        set_time_limit(600); // 10 minutes
        
        if (!file_exists($backupFile)) {
            throw new Exception('Backup file does not exist');
        }
        
        if (!class_exists('ZipArchive')) {
            throw new Exception('ZipArchive class not available');
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($backupFile);
        if ($result !== TRUE) {
            throw new Exception('Cannot open backup file');
        }
        
        // Extract to parent directory
        $extractPath = '../';
        $extractResult = $zip->extractTo($extractPath);
        
        if (!$extractResult) {
            throw new Exception('Failed to extract files');
        }
        
        $numFiles = $zip->numFiles;
        $zip->close();
        
        return [
            'success' => true, 
            'message' => "Files restored successfully ($numFiles files extracted)"
        ];
        
    } catch (Exception $e) {
        error_log("Files restore error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Files restore failed: ' . $e->getMessage()];
    }
}

function formatBytes($size, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    
    for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
        $size /= 1024;
    }
    
    return round($size, $precision) . ' ' . $units[$i];
}

function logBackupActivity($adminId, $action, $type, $backupName) {
    try {
        require_once '../config/database.php';
        $database = new Database();
        $db = $database->getConnection();
        
        $query = "INSERT INTO admin_activity_log (admin_id, action, target_type, target_id, details, ip_address) 
                  VALUES (:admin_id, :action, 'backup', NULL, :details, :ip_address)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':admin_id', $adminId);
        $stmt->bindParam(':action', $action);
        $stmt->bindValue(':details', "Backup: $backupName, Type: $type");
        $stmt->bindValue(':ip_address', $_SERVER['REMOTE_ADDR'] ?? 'unknown');
        $stmt->execute();
        
    } catch (Exception $e) {
        // Log error but don't fail the main operation
        error_log("Failed to log backup activity: " . $e->getMessage());
    }
}
?>