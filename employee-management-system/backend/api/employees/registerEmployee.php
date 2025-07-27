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
	$name = $_POST['name'] ?? '';
	$email = $_POST['email'] ?? '';
	$phone = $_POST['phone'] ?? '';
	$username = $_POST['username'] ?? '';
	$department_id = $_POST['department_id'] ?? null;

	if(!$name || !$email || ! $phone || !$username || !$department_id)
	{
		echo json_encode(['success' => false, 'message' => 'All fields are required']);
		exit;
	}

	$stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, join_date, status, department_id) VALUES(?,?,?,CURDATE(), 'active',?)");

	$stmt->execute([$name, $email, $phone,$department_id]);
	$employeeId = $pdo->lastInsertId();

	//Upload exeprience letter, academic certificates etc

	$uploadDir = '../../uploads/';
	$docFields = ['certificate','experience'];

	foreach ($docFields as $docType) 
	{
		if(isset($_FILES[$docType]) && $_FILES[$docType]['error'] === 0)
		{
			$filename = time() . '-'. basename($_FILES[$docType]['name']);

			$filepath = $uploadDir . $filename;

			move_uploaded_file($_FILES[$docType]['tmp_name'], $filepath);

			$docInsert = $pdo->prepare("INSERT INTO documents(employee_id, doc_type, file_path) VALUES (?,?,?)");
			$docInsert->execute([$employeeId, $docType, $filename]);
		}
	}

	//Password Hashing
	$rawPassword = bin2hex(random_bytes(4));

	$hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

	$userInsert = $pdo->prepare("INSERT INTO users (username, password, role, employee_id, status) VALUES (?,?,'employee',?,'active')");
	$userInsert->execute([$username, $hashedPassword, $employeeId]);

	//Mail Send

	$subject = "Your Employee Account is Ready";

	$message = "
				<p>Dear $name,</p>
				<p>Your employee account has been created successfully. Please find your login credentilas bellow:</p>
				<p><b>Username:</b>$username</p><br>
				<p><b>Password:</b>$rawPassword</p>
				<p>You may log in here: <a href='//localhost/employee-management-system'>Employee Portal</a></p>
				<p>Thank you, <br> HR Department</p>";

	$mailSent = $admin->sendMail($email, $message, $subject);

	if($mailSent)
	{
		echo json_encode(['success' => true, 'message' =>'Employee registered and mail sent successfully']);
	}

	else
	{
		echo json_encode(['success' => true, 'message' =>'Employee registered but mail not sent']);
	}
} 
catch (Exception $e) 
{
	echo json_encode(['success' => false, 'message' =>'Error:' .$e->getMessage()]);
}

?>