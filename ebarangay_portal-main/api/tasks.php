<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

require_once '../config/database.php';
require_once '../classes/Task.php';

$database = new Database();
$db = $database->getConnection();
$task = new Task($db);

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$user_id = $_SESSION['user_id'];

switch($action) {
    case 'get_all':
        $stmt = $task->getUserTasks($user_id);
        $tasks = [];
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = [
                'id' => $row['id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'priority' => $row['priority'],
                'category' => $row['category'],
                'completed' => (bool)$row['completed'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
                'completed_at' => $row['completed_at']
            ];
        }
        
        echo json_encode(['success' => true, 'tasks' => $tasks]);
        break;
        
    case 'create':
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $category = $_POST['category'] ?? 'other';
        
        if(empty($title)) {
            echo json_encode(['success' => false, 'message' => 'Title is required']);
            exit;
        }
        
        $task->title = $title;
        $task->description = $description;
        $task->priority = $priority;
        $task->category = $category;
        $task->user_id = $user_id;
        
        $task_id = $task->create();
        
        if($task_id) {
            echo json_encode(['success' => true, 'message' => 'Task created successfully', 'task_id' => $task_id]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create task']);
        }
        break;
        
    case 'update':
        $id = $_POST['id'] ?? '';
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $priority = $_POST['priority'] ?? 'medium';
        $category = $_POST['category'] ?? 'other';
        $completed = isset($_POST['completed']) ? (bool)$_POST['completed'] : false;
        
        if(empty($id) || empty($title)) {
            echo json_encode(['success' => false, 'message' => 'ID and title are required']);
            exit;
        }
        
        $task->id = $id;
        $task->title = $title;
        $task->description = $description;
        $task->priority = $priority;
        $task->category = $category;
        $task->completed = $completed;
        $task->user_id = $user_id;
        
        if($task->update()) {
            echo json_encode(['success' => true, 'message' => 'Task updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update task']);
        }
        break;
        
    case 'toggle':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Task ID is required']);
            exit;
        }
        
        $task->id = $id;
        $task->user_id = $user_id;
        
        if($task->toggleComplete()) {
            echo json_encode(['success' => true, 'message' => 'Task status updated']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update task status']);
        }
        break;
        
    case 'delete':
        $id = $_POST['id'] ?? '';
        
        if(empty($id)) {
            echo json_encode(['success' => false, 'message' => 'Task ID is required']);
            exit;
        }
        
        $task->id = $id;
        $task->user_id = $user_id;
        
        if($task->delete()) {
            echo json_encode(['success' => true, 'message' => 'Task deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete task']);
        }
        break;
        
    case 'get_stats':
        $stats = $task->getStats($user_id);
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>