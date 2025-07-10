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
            $backupType = $_POST['backup_type'] ?? 'full';
            $backupName = trim($_POST['backup_name'] ?? '');
            if (empty($backupName)) {
                $backupName = 'backup_' . date('Y-m-d_H-i-s');
            }
            
            // Sanitize backup name
            $backupName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $backupName);
            
            $backupDir = '../backups/';
            if (!is_dir($backupDir)) {
                if (!mkdir($backupDir, 0755, true)) {
                    throw new Exception('Failed to create backup directory');
                }
            }
            
            // Check if backup directory is writable
            if (!is_writable($backupDir)) {
                throw new Exception('Backup directory is not writable. Please check permissions.');
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
            
            // Files backup (using tar/folder copy instead of ZipArchive)
            if ($backupType === 'files' || $backupType === 'full') {
                $filesBackupFile = $backupDir . $backupName . '_files';
                $filesResult = createFilesBackupAlternative($filesBackupFile);
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
            $successMessages = [];
            
            foreach ($result as $type => $res) {
                if (!$res['success']) {
                    $hasFailures = true;
                    $errorMessages[] = ucfirst($type) . ": " . $res['message'];
                } else {
                    $successMessages[] = ucfirst($type) . " backup created successfully";
                }
            }
            
            $message = implode(', ', $successMessages);
            if ($hasFailures) {
                $message = 'Backup partially completed. ' . $message;
                if (!empty($errorMessages)) {
                    $message .= '. Errors: ' . implode(', ', $errorMessages);
                }
            }
            
            echo json_encode([
                'success' => !empty($successMessages), 
                'message' => $message,
                'backup_name' => $backupName,
                'total_size' => $totalSize,
                'result' => $result,
                'has_failures' => $hasFailures
            ]);
            
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
                
                // Check for files backup directories
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..' && is_dir($backupDir . $file) && strpos($file, '_files') !== false) {
                        $dirSize = getDirSize($backupDir . $file);
                        $stats['total_size'] += $dirSize;
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
                    if ($file != '.' && $file != '..') {
                        $filePath = $backupDir . $file;
                        $fileInfo = pathinfo($file);
                        
                        if (is_file($filePath)) {
                            $baseName = preg_replace('/_(database|files)$/', '', $fileInfo['filename']);
                            
                            if (!isset($backupGroups[$baseName])) {
                                $backupGroups[$baseName] = [
                                    'name' => $baseName,
                                    'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                                    'database' => false,
                                    'files' => false,
                                    'size' => 0
                                ];
                            }
                            
                            if (strpos($file, '_database.sql') !== false) {
                                $backupGroups[$baseName]['database'] = true;
                                $backupGroups[$baseName]['database_file'] = $file;
                                $backupGroups[$baseName]['database_size'] = filesize($filePath);
                            }
                            
                            $backupGroups[$baseName]['size'] += filesize($filePath);
                        } elseif (is_dir($filePath) && strpos($file, '_files') !== false) {
                            $baseName = str_replace('_files', '', $file);
                            
                            if (!isset($backupGroups[$baseName])) {
                                $backupGroups[$baseName] = [
                                    'name' => $baseName,
                                    'created_at' => date('Y-m-d H:i:s', filemtime($filePath)),
                                    'database' => false,
                                    'files' => false,
                                    'size' => 0
                                ];
                            }
                            
                            $backupGroups[$baseName]['files'] = true;
                            $backupGroups[$baseName]['files_dir'] = $file;
                            $dirSize = getDirSize($filePath);
                            $backupGroups[$baseName]['files_size'] = $dirSize;
                            $backupGroups[$baseName]['size'] += $dirSize;
                        }
                    }
                }
                
                $backups = array_values($backupGroups);
                usort($backups, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
                
                // Format sizes and determine status
                foreach ($backups as &$backup) {
                    $backup['size_formatted'] = formatBytes($backup['size']);
                    
                    // Determine backup status
                    if ($backup['database'] && $backup['files']) {
                        $backup['status'] = 'Complete';
                        $backup['type'] = 'Full Backup';
                    } elseif ($backup['database']) {
                        $backup['status'] = 'Partial';
                        $backup['type'] = 'Database Only';
                    } elseif ($backup['files']) {
                        $backup['status'] = 'Partial';
                        $backup['type'] = 'Files Only';
                    } else {
                        $backup['status'] = 'Incomplete';
                        $backup['type'] = 'Unknown';
                    }
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
                $filesBackupDir = $backupDir . $backupName . '_files';
                if (is_dir($filesBackupDir)) {
                    $filesResult = restoreFilesBackupAlternative($filesBackupDir);
                    $result['files'] = $filesResult;
                } else {
                    $result['files'] = ['success' => false, 'message' => 'Files backup directory not found'];
                }
            }
            
            // Log restore activity
            logBackupActivity($_SESSION['admin_id'], 'restore_backup', $restoreType, $backupName);
            
            // Check results
            $hasFailures = false;
            $errorMessages = [];
            $successMessages = [];
            
            foreach ($result as $type => $res) {
                if (!$res['success']) {
                    $hasFailures = true;
                    $errorMessages[] = ucfirst($type) . ": " . $res['message'];
                } else {
                    $successMessages[] = ucfirst($type) . " restored successfully";
                }
            }
            
            $message = implode(', ', $successMessages);
            if ($hasFailures) {
                if (!empty($successMessages)) {
                    $message = 'Restore partially completed. ' . $message;
                } else {
                    $message = 'Restore failed';
                }
                if (!empty($errorMessages)) {
                    $message .= '. Errors: ' . implode(', ', $errorMessages);
                }
            }
            
            echo json_encode([
                'success' => !empty($successMessages), 
                'message' => $message,
                'result' => $result,
                'has_failures' => $hasFailures
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
            
            // Delete files backup directory
            $filesDir = $backupDir . $backupName . '_files';
            if (is_dir($filesDir)) {
                deleteDirectory($filesDir);
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
        
        if ($backupType === 'database') {
            $filename = $backupName . '_database.sql';
            $filepath = $backupDir . $filename;
            
            if (file_exists($filepath)) {
                header('Content-Type: application/sql');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);
                exit;
            }
        } elseif ($backupType === 'files') {
            $filesDir = $backupDir . $backupName . '_files';
            if (is_dir($filesDir)) {
                // Create a temporary zip for download
                $tempZip = $backupDir . $backupName . '_files_temp.zip';
                if (createZipFromDirectory($filesDir, $tempZip)) {
                    header('Content-Type: application/zip');
                    header('Content-Disposition: attachment; filename="' . $backupName . '_files.zip"');
                    header('Content-Length: ' . filesize($tempZip));
                    readfile($tempZip);
                    unlink($tempZip); // Clean up temp file
                    exit;
                }
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Backup file not found']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

// Helper functions
function createDatabaseBackup($db, $backupFile) {
    try {
        set_time_limit(300); // 5 minutes
        
        // Get all tables in the correct order (respecting foreign key dependencies)
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        if (empty($tables)) {
            throw new Exception('No tables found in database');
        }
        
        // Sort tables to handle dependencies properly
        // Put tables with no foreign keys first, then dependent tables
        $sortedTables = sortTablesByDependencies($db, $tables);
        
        $sql = "-- E-Barangay Portal Database Backup\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Tables: " . implode(', ', $sortedTables) . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n";
        $sql .= "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO';\n";
        $sql .= "SET AUTOCOMMIT = 0;\n";
        $sql .= "START TRANSACTION;\n\n";
        
        $totalRows = 0;
        
        foreach ($sortedTables as $table) {
            try {
                // Get table structure
                $result = $db->query("SHOW CREATE TABLE `$table`");
                $row = $result->fetch(PDO::FETCH_ASSOC);
                $sql .= "-- Table structure for table `$table`\n";
                $sql .= "DROP TABLE IF EXISTS `$table`;\n";
                $sql .= $row['Create Table'] . ";\n\n";
                
                // Get table data
                $result = $db->query("SELECT * FROM `$table`");
                $rowCount = $result->rowCount();
                
                if ($result->rowCount() > 0) {
                    $sql .= "-- Dumping data for table `$table`\n";
                    $sql .= "-- $rowCount rows\n";
                    
                    // Get column names for INSERT statement
                    $columns = [];
                    $columnResult = $db->query("SHOW COLUMNS FROM `$table`");
                    while ($col = $columnResult->fetch(PDO::FETCH_ASSOC)) {
                        $columns[] = '`' . $col['Field'] . '`';
                    }
                    
                    $sql .= "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES\n";
                    
                    $rows = [];
                    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                        $values = array_map(function($value) use ($db) {
                            return $value === null ? 'NULL' : $db->quote($value);
                        }, array_values($row));
                        $rows[] = '(' . implode(', ', $values) . ')';
                    }
                    $sql .= implode(",\n", $rows) . ";\n";
                    $totalRows += $rowCount;
                } else {
                    $sql .= "-- No data for table `$table`\n\n";
                }
            } catch (Exception $e) {
                error_log("Error backing up table $table: " . $e->getMessage());
                $sql .= "-- Error backing up table $table: " . $e->getMessage() . "\n\n";
            }
        }
        
        $sql .= "COMMIT;\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sql .= "SET AUTOCOMMIT = 1;\n";
        $sql .= "-- Backup completed on: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Total rows backed up: $totalRows\n";
        
        $bytesWritten = file_put_contents($backupFile, $sql);
        
        if ($bytesWritten === false) {
            throw new Exception('Failed to write backup file');
        }
        
        return [
            'success' => true, 
            'message' => 'Database backup created successfully',
            'file' => basename($backupFile),
            'size' => filesize($backupFile),
            'tables_count' => count($sortedTables),
            'total_rows' => $totalRows
        ];
        
    } catch (Exception $e) {
        error_log("Database backup error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database backup failed: ' . $e->getMessage()];
    }
}

function sortTablesByDependencies($db, $tables) {
    // Simple dependency sorting - put tables with no foreign keys first
    $independent = [];
    $dependent = [];
    
    foreach ($tables as $table) {
        try {
            // Check if table has foreign keys
            $result = $db->query("SELECT COUNT(*) as fk_count FROM information_schema.KEY_COLUMN_USAGE 
                                 WHERE TABLE_SCHEMA = DATABASE() 
                                 AND TABLE_NAME = '$table' 
                                 AND REFERENCED_TABLE_NAME IS NOT NULL");
            $row = $result->fetch(PDO::FETCH_ASSOC);
            
            if ($row['fk_count'] > 0) {
                $dependent[] = $table;
            } else {
                $independent[] = $table;
            }
        } catch (Exception $e) {
            // If we can't determine dependencies, put it in independent
            $independent[] = $table;
        }
    }
    
    // Return independent tables first, then dependent ones
    return array_merge($independent, $dependent);
}

function createFilesBackupAlternative($backupDir) {
    try {
        set_time_limit(600); // 10 minutes
        
        if (!mkdir($backupDir, 0755, true)) {
            throw new Exception('Cannot create backup directory');
        }
        
        $fileCount = 0;
        $totalSize = 0;
        
        // Copy uploads directory
        $uploadsDir = '../uploads/';
        if (is_dir($uploadsDir)) {
            $targetDir = $backupDir . '/uploads';
            $result = copyDirectory($uploadsDir, $targetDir);
            $fileCount += $result['files'];
            $totalSize += $result['size'];
        }
        
        // Copy assets directory
        $assetsDir = '../assets/';
        if (is_dir($assetsDir)) {
            $targetDir = $backupDir . '/assets';
            $result = copyDirectory($assetsDir, $targetDir);
            $fileCount += $result['files'];
            $totalSize += $result['size'];
        }
        
        // Copy config files (excluding sensitive data)
        $configDir = $backupDir . '/config';
        if (!is_dir($configDir)) {
            mkdir($configDir, 0755, true);
        }
        
        $configFiles = ['../config/database.php'];
        foreach ($configFiles as $file) {
            if (file_exists($file)) {
                $targetFile = $configDir . '/' . basename($file);
                if (copy($file, $targetFile)) {
                    $fileCount++;
                    $totalSize += filesize($file);
                }
            }
        }
        
        // Create backup info file
        $infoFile = $backupDir . '/backup_info.txt';
        $info = "E-Barangay Portal Files Backup\n";
        $info .= "Created: " . date('Y-m-d H:i:s') . "\n";
        $info .= "Files: $fileCount\n";
        $info .= "Size: " . formatBytes($totalSize) . "\n";
        file_put_contents($infoFile, $info);
        
        return [
            'success' => true, 
            'message' => 'Files backup created successfully',
            'directory' => basename($backupDir),
            'size' => $totalSize,
            'files_count' => $fileCount
        ];
        
    } catch (Exception $e) {
        error_log("Files backup error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Files backup failed: ' . $e->getMessage()];
    }
}

function copyDirectory($source, $destination) {
    $fileCount = 0;
    $totalSize = 0;
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    
    foreach ($iterator as $item) {
        $target = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
        
        if ($item->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0755, true);
            }
        } else {
            if (copy($item, $target)) {
                $fileCount++;
                $totalSize += $item->getSize();
            }
        }
    }
    
    return ['files' => $fileCount, 'size' => $totalSize];
}

function restoreDatabaseBackup($db, $backupFile) {
    try {
        set_time_limit(300); // 5 minutes
        
        if (!file_exists($backupFile)) {
            throw new Exception('Backup file does not exist');
        }
        
        $sql = file_get_contents($backupFile);
        
        if ($sql === false) {
            throw new Exception('Failed to read backup file');
        }
        
        // Disable foreign key checks and autocommit for proper restoration
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
        $db->exec("SET AUTOCOMMIT = 0");
        
        $db->beginTransaction();
        
        // Split SQL into individual statements - improved parsing
        $statements = [];
        $currentStatement = '';
        $lines = explode("\n", $sql);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || preg_match('/^--/', $line) || preg_match('/^\/\*/', $line)) {
                continue;
            }
            
            $currentStatement .= $line . "\n";
            
            // Check if statement is complete (ends with semicolon)
            if (preg_match('/;\s*$/', $line)) {
                $statements[] = trim($currentStatement);
                $currentStatement = '';
            }
        }
        
        $executedStatements = 0;
        $errors = [];
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                // Skip certain control statements that we handle separately
                if (preg_match('/^(SET|START TRANSACTION|COMMIT|LOCK TABLES|UNLOCK TABLES)/i', $statement)) {
                    continue;
                }
                
                try {
                    $db->exec($statement);
                    $executedStatements++;
                } catch (Exception $e) {
                    $errors[] = "Statement error: " . $e->getMessage();
                    error_log("Error executing SQL statement: " . $statement . " - " . $e->getMessage());
                    // Continue with other statements even if one fails
                }
            }
        }
        
        $db->commit();
        
        // Re-enable foreign key checks
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        $db->exec("SET AUTOCOMMIT = 1");
        
        $message = "Database restored successfully ($executedStatements statements executed)";
        if (!empty($errors)) {
            $message .= ". " . count($errors) . " errors occurred but restore completed.";
            error_log("Restore errors: " . implode("; ", $errors));
        }
        
        return [
            'success' => true, 
            'message' => $message,
            'statements_executed' => $executedStatements,
            'errors_count' => count($errors),
            'errors' => $errors
        ];
        
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        
        // Try to re-enable foreign key checks even on failure
        try {
            $db->exec("SET FOREIGN_KEY_CHECKS = 1");
            $db->exec("SET AUTOCOMMIT = 1");
        } catch (Exception $cleanupError) {
            error_log("Cleanup error: " . $cleanupError->getMessage());
        }
        
        error_log("Database restore error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Database restore failed: ' . $e->getMessage()];
    }
}

function restoreFilesBackupAlternative($backupDir) {
    try {
        set_time_limit(600); // 10 minutes
        
        if (!is_dir($backupDir)) {
            throw new Exception('Backup directory does not exist');
        }
        
        $restoredFiles = 0;
        
        // Restore uploads directory
        $uploadsBackup = $backupDir . '/uploads';
        if (is_dir($uploadsBackup)) {
            $targetDir = '../uploads/';
            $result = copyDirectory($uploadsBackup, $targetDir);
            $restoredFiles += $result['files'];
        }
        
        // Restore assets directory
        $assetsBackup = $backupDir . '/assets';
        if (is_dir($assetsBackup)) {
            $targetDir = '../assets/';
            $result = copyDirectory($assetsBackup, $targetDir);
            $restoredFiles += $result['files'];
        }
        
        // Restore config files
        $configBackup = $backupDir . '/config';
        if (is_dir($configBackup)) {
            $targetDir = '../config/';
            $result = copyDirectory($configBackup, $targetDir);
            $restoredFiles += $result['files'];
        }
        
        return [
            'success' => true, 
            'message' => "Files restored successfully ($restoredFiles files)",
            'files_restored' => $restoredFiles
        ];
        
    } catch (Exception $e) {
        error_log("Files restore error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Files restore failed: ' . $e->getMessage()];
    }
}

function deleteDirectory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

function getDirSize($dir) {
    $size = 0;
    
    if (!is_dir($dir)) {
        return 0;
    }
    
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)) as $file) {
        $size += $file->getSize();
    }
    
    return $size;
}

function createZipFromDirectory($sourceDir, $zipFile) {
    if (!class_exists('ZipArchive')) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
        return false;
    }
    
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sourceDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($iterator as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen(realpath($sourceDir)) + 1);
            $zip->addFile($filePath, str_replace('\\', '/', $relativePath));
        }
    }
    
    return $zip->close();
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
        error_log("Failed to log backup activity: " . $e->getMessage());
    }
}
?>