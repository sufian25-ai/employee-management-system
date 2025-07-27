<?php
header('Content-Type: application/json');

session_start();

if($_SERVER['REQUEST_METHOD'] !== 'GET')
{
	echo json_encode(['success' => false, 'message'=>'Invalid Request']);
	exit;
}

require_once '../../classes/class.admin.php';

$admin = new Admin();

try 
{
	$weekends = $admin->getWeekends();
	echo json_encode([
		'success' => true,
		'data' => $weekends
	]);
} 
catch (Exception $e)
 {
	echo json_encode(['success' => false, 'message' => 'Failed to get weekends info']);
}
?>