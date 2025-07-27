<?php
date_default_timezone_set('Asia/Dhaka');
header("Content-Type: application/json");

session_start();

if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
	echo json_encode(['success' => false, 'message' => 'Invalid request method']);
	exit;
}

//Must be logged in and a valid employee

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'employee')
{
	echo json_encode(['success' => false, 'message' => 'Unauthorised access']);
	exit;
}

require_once '../../classes/class.admin.php';

$admin = new Admin();

$employeeId = $_SESSION['user']['employee_id'];
$today = date('Y-m-d');

//Check if already marked today
$result = $admin->checkTodayAttendance($employeeId);

if($result['already_marked'])
{
	echo json_encode([
		'success' => false,
		'message' => 'Attendance already marked today',
		'time' => $result['check_in']	
	]);
	exit;
}

$attendanceResult = $admin->recordAttendance($employeeId);

echo json_encode($attendanceResult)
?>