<?php
session_start();
header('Content-Type: application/json');
if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin')
{
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}
if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit;
}
$data = json_decode(file_get_contents("php://input"), true);
$id = $data['id'] ?? null;
if(!$id)
{
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit;
}
require_once '../../classes/class.admin.php';
$admin = new Admin();
$pdo = $admin->getPdo();
try 
{
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    
    if($stmt->rowCount() > 0)
    {
        echo json_encode(['success' => true, 'message' => 'Department Deleted']);
    }
    else
    {
        echo json_encode(['success' => false, 'message' => 'Department not found or already deleted']);
    }
} 
catch (Exception $e) 
{
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}


?>