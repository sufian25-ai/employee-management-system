<?php
session_start();
header('Content-Type: application/json');

require_once '../../classes/class.admin.php';

$admin = new Admin();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin')
{
	echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
	exit;
}

$data = json_decode(file_get_contents("php://input"), true);

$group_name = trim($data['group_name'] ?? '');
$description = trim($data['description'] ?? '');
$employees_ids = $data['employee_ids'] ?? [];

if($group_name === '' || empty($employees_ids))
{
	echo json_encode(['success'=> false, 'message' => 'Group name and employee list are required.']);
	exit;
} 

$pdo = $admin->getPdo();

try 
{
	$pdo->beginTransaction();

	$group_id = $admin->createGroup($group_name, $description, $_SESSION['user']['id']);

	$admin->addEmployeesToGroup($group_id, $employees_ids);

	$pdo->commit();

	echo json_encode(['success' => true, 'message' =>'Group Created Successfully']);
} 
catch (Exception $e) 
{
	$pdo->rollBack();
	echo json_encode(['success' => false, 'message' => 'Failed to create group', 'error' => $e->getMessage()]);
}


?>