<?php
session_start();
header("Content-Type: application/json");

//Allow only HR/Admin

if(!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'], ['admin','hr']))
{
	echo json_encode(['success' => false, 'message' => 'Unauthorized']);
	exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
	echo json_encode(['success' => false, 'message' => 'Invalid Request']);
	exit;
}

require_once '../../classes/class.admin.php';

$admin = new Admin();

$data = json_decode(file_get_contents("php://input"), true);

$employeeId = $data['employee_id'] ?? null;

$month = $data['month'] ?? null;

$year = $data['year'] ?? null;

$bonus = $data['bonus'] ?? 0;

$deduction = $data['deduction'] ?? 0;

$overTimeHours = $data['overtime'] ?? 0;

if(!$employeeId || !$month || !$year)
{
	echo json_encode(['success' => false, 'message' => 'Missing required fields']);
	exit;
}

$existing = $admin->payrollExists($employeeId, $month, $year);

if($existing)
{
	echo json_encode(['success' => false, 'message' =>"Payroll already generated"]);
	exit;
}

$result = $admin->generatePayroll($employeeId, $month, $year, $bonus, $deduction, $overTimeHours);
echo json_encode($result);




?>