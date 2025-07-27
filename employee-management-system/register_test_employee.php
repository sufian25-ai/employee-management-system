<?php
$host = "localhost";
		$db = "ems_db";
		$user = "root";
		$pass = "";
		$charset = "utf8mb4";

		$dsn = "mysql:host=$host; dbname=$db; charset=$charset";
		$options = [

			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
		];

		try
		{
			$pdo = new PDO($dsn, $user, $pass, $options);
			
		}

		catch(PDOException $e)
		{
			die("DB Connection Failed: ".$e->getMessage());
		}
$empName = "Test Employee";
$email = "demoemp@email.com";
$phone = "01711454214";
$username = "employee1";
$password_raw = "123456";
$password_hashed = password_hash($password_raw, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if($stmt->rowCount() > 0)
{
	exit("user already exists");
}

$empInsert = $pdo->prepare("INSERT INTO employees (name, email, phone, join_date, status) VALUES(?,?,?,CURDATE(), 'active')");
$empInsert->execute([$empName, $email, $phone]);
$employeeId = $pdo->lastInsertId();

$userInsert = $pdo->prepare("INSERT INTO users(username, password, role, employee_id, status) VALUES(?,?,'employee',?,'active')");
$userInsert->execute([$username, $password_hashed, $employeeId]);

echo "Test employee '$username' created with password '$password_raw'";
?>