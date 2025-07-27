<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user']) || !in_array($_SESSION['user']['role'],['admin','hr']))
{
	echo json_encode(['success' => false, 'message' => 'Unauthorized Access']);
	exit;
}

if($_SERVER['REQUEST_METHOD'] !== 'POST')
{
	echo json_encode(['success' => false, 'message' => 'Invalid Request']);
	exit;
}

require_once '../../classes/class.admin.php';

$admin = new Admin();

$pdo = $admin->getPdo();

try 
{
	$employeeId = $_POST['employee_id'] ?? '';
	$name = $_POST['name'] ?? '';
	$email = $_POST['email'] ?? '';
	$phone = $_POST['phone'] ?? '';
	$username = $_POST['username'] ?? '';
	$department_id = $_POST['department_id'] ?? null;

	if(!$employeeId || !$name || !$email || !$phone || !$username || !$department_id)
	{
		echo json_encode(['success' => false, 'message' => 'All fields are required']);
		exit;
	}	

	$stmt = $pdo->prepare(
					"UPDATE employees SET name = ?, email = ?, phone = ?,
					department_id = ? WHERE id = ?");
	$stmt->execute([$name, $email, $phone, $department_id, $employeeId]);

	 $userStmt = $pdo->prepare("UPDATE users SET username = ? WHERE employee_id = ?");
	 $userStmt->execute([$username, $employeeId]);

	 $uploadDir = '../../uploads/';
	 $docFields = ['certificate', 'experience'];

	 foreach ($docFields as $docType)
	 {
	 	if(isset($_FILES[$docType]) && $_FILES[$docType]['error'] === 0)
	 	{
	 		$filename = time() . '_' .basename($_FILES[$docType]['name']);
	 		$filePath =  $uploadDir . $filename;

	 		move_uploaded_file($_FILES[$docType]['tmp_name'], $filePath);

	 		$check = $pdo->prepare("SELECT id FROM documents WHERE employee_id = ? AND doc_type = ?");

	 		$check->execute([$employeeId, $docType]);

	 		if($check->rowCount() > 0)
	 		{
	 			$update = $pdo->prepare("UPDATE documents SET file_path = ? WHERE employee_id = ? AND doc_type = ?");

	 			$update->execute([$filename, $employeeId, $docType]);
	 		}

	 		else
	 		{
	 			$insert = $pdo->prepare("INSERT INTO documents (employee_id, doc_type, file_path) VALUES (?,?,?)");
	 			$insert->execute([$employeeId, $docType, $filename]);
	 		}
	 	}	
	 }

	 echo json_encode(['success' => true, 'message' => 'Employee and user updated successfully']);
} 
catch (Exception $e) 
{
	echo json_encode(['success' => false, 'message' => 'Error:' .$e->getMessage()]);
}
?>