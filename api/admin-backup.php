<?php
session_start();
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
            $backupName = $_POST['backup_name'] ?? 'backup_' . date('Y-m-d_H-i-s');
            
            $backupDir = '../backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $result = [];
            
            // Database backup
            if ($backupType === 'database' || $backupType === 'full') {
                $dbBackupFile = $backupDir . $backupName . '_database.sql';
                $dbResult = createDatabaseBackup($db, $dbBackupFile);
                $result['database'] = $dbResult;
            }
            
            // Files backup
            if ($backupType === 'files' || $backupType === 'full') {
                $filesBackupFile = $backupDir . $backupName . '_files.zip';
                $filesResult = createFilesBackup($filesBackupFile);
                $result['files'] = $filesResult;
            }
            
            // Log backup activity
            logBackupActivity($_SESSION['admin_id'], 'create_backup', $backupType, $backupName);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Backup created successfully',
                'backup_name' => $backupName,
                'result' => $result
            ]);
            
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Backup failed: ' . $e->getMessage()]);
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
                        } elseif (strpos($file, '_files.zip') !== false) {
                            $backupGroups[$baseName]['files'] = true;
                            $backupGroups[$baseName]['files_file'] = $file;
                        }
                        
                        $backupGroups[$baseName]['size'] += filesize($backupDir . $file);
                    }
                }
                
                $backups = array_values($backupGroups);
                usort($backups, function($a, $b) {
                    return strtotime($b['created_at']) - strtotime($a['created_at']);
                });
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
            
            echo json_encode([
                'success' => true, 
                'message' => 'Restore completed',
                'result' => $result
            ]);
            
        } catch (Exception $e) {
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
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }
        
        $sql = "-- E-Barangay Portal Database Backup\n";
        $sql .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        
        foreach ($tables as $table) {
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
        }
        
        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        
        file_put_contents($backupFile, $sql);
        
        return [
            'success' => true, 
            'message' => 'Database backup created',
            'file' => basename($backupFile),
            'size' => filesize($backupFile)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Database backup failed: ' . $e->getMessage()];
    }
}

function createFilesBackup($backupFile) {
    try {
        $zip = new ZipArchive();
        if ($zip->open($backupFile, ZipArchive::CREATE) !== TRUE) {
            throw new Exception('Cannot create zip file');
        }
        
        // Add uploads directory
        $uploadsDir = '../uploads/';
        if (is_dir($uploadsDir)) {
            addDirectoryToZip($zip, $uploadsDir, 'uploads/');
        }
        
        // Add assets directory
        $assetsDir = '../assets/';
        if (is_dir($assetsDir)) {
            addDirectoryToZip($zip, $assetsDir, 'assets/');
        }
        
        // Add config files (excluding sensitive data)
        $configFiles = ['../config/database.php'];
        foreach ($configFiles as $file) {
            if (file_exists($file)) {
                $zip->addFile($file, 'config/' . basename($file));
            }
        }
        
        $zip->close();
        
        return [
            'success' => true, 
            'message' => 'Files backup created',
            'file' => basename($backupFile),
            'size' => filesize($backupFile)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Files backup failed: ' . $e->getMessage()];
    }
}

function addDirectoryToZip($zip, $dir, $zipPath) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if (!$file->isDir()) {
            $filePath = $file->getRealPath();
            $relativePath = $zipPath . substr($filePath, strlen(realpath($dir)) + 1);
            $zip->addFile($filePath, $relativePath);
        }
    }
}

function restoreDatabaseBackup($db, $backupFile) {
    try {
        $sql = file_get_contents($backupFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        $db->beginTransaction();
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                $db->exec($statement);
            }
        }
        
        $db->commit();
        
        return [
            'success' => true, 
            'message' => 'Database restored successfully'
        ];
        
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'message' => 'Database restore failed: ' . $e->getMessage()];
    }
}

function restoreFilesBackup($backupFile) {
    try {
        $zip = new ZipArchive();
        if ($zip->open($backupFile) !== TRUE) {
            throw new Exception('Cannot open backup file');
        }
        
        // Extract to parent directory
        $extractPath = '../';
        $zip->extractTo($extractPath);
        $zip->close();
        
        return [
            'success' => true, 
            'message' => 'Files restored successfully'
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Files restore failed: ' . $e->getMessage()];
    }
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