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
$name = trim($data['name'] ?? '');  
if($name === '')
{
    echo json_encode(['success' => false, 'message' => 'Department name required']);
    exit;
}
require_once '../../classes/class.admin.php';
$admin = new Admin();
$pdo = $admin->getPdo();
try
{
    $stmt = $pdo->prepare("UPDATE departments SET name = ? WHERE id = ?");
    $stmt->execute([$name, $data['id']]);
    
    if($stmt->rowCount() > 0)
    {
        echo json_encode(['success' => true, 'message' => 'Department Updated']);
    }
    else
    {
        echo json_encode(['success' => false, 'message' => 'No changes made or department not found']);
    }
} 
catch (Exception $e) 
{
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

?>