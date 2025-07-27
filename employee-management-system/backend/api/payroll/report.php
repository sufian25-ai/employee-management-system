<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'],['admin','hr']))
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

$month = $data['month'] ?? null;
$year = $data['year'] ?? null;

if(!$month || !$year)
{
	echo json_encode(['success' => false, 'message' => 'Month and Year Required']);
	exit;
}

require_once '../../classes/class.admin.php';
$admin = new Admin();

try 
{
	$report = $admin->getPayrollReport($month, $year);
	echo json_encode(['success'=> true, 'data' => $report]);
}
 catch (Exception $e) 
{
	echo json_encode(['success' => false, 'message'=>'Failed to fetch payroll report']);
}



?>