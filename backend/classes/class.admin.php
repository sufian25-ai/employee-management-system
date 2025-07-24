<?php
class Admin
{
	private $pdo;

	public function __construct()
	{
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
			$this->pdo = new PDO($dsn, $user, $pass, $options);
		}

		catch(PDOException $e)
		{
			die("DB Connection Failed: ".$e->getMessage());
		}

	}

	public function getPdo()
	{
		return $this->pdo;
	}

	//User Login

	public function login($username, $password)
	{
		$stmt = $this->pdo->prepare("SELECT * FROM users WHERE username=? AND status = 'active'");

		$stmt->execute([$username]);
		$user = $stmt->fetch();

		if($user && password_verify($password, $user['password']))
		{
			//Success
			return [

				'success'=>true,
				'data' => [
						'id'=>$user['id'],
						'employee_id'=>$user['employee_id'],
						'role' => $user['role'],
				],
			];
		}

		else
		{
			return ['success'=> false, 'message'=>'Invalid login credentials'];
		}
	}

	//Record Attendance

	public function recordAttendance($employeeId)
	{
		$today = date('Y-m-d');
		$timeNow = date('h:i:s');

		//Check if already marked today

		$check = $this->pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
		$check->execute([$employeeId, $today]); 

		if($check->rowCount() > 0)
		{
			return ['success'=> false, 'message' => 'Already marked today'];
		}

		//Check if today is weekend or holiday
		$day = date('l');
		$holidayCheck = $this->pdo->prepare("SELECT id FROM holidays WHERE holiday_date = ?");
		$holidayCheck ->execute([$today]);
		$isHoliday = $holidayCheck->rowCount() > 0 ? 1 : 0;

		$weekendCheck = $this->pdo->prepare("SELECT id FROM weekends WHERE day_of_week	= ?");

		$weekendCheck->execute([$day]);

		$isWeekend =  $weekendCheck->rowCount() > 0 ? 1 : 0;

		//Get login rules
		$rule = $this->pdo->prepare("SELECT * FROM login_rules WHERE employee_id = ?");
		$rule->execute([$employeeId]);
		$loginRule = $rule->fetch();

		$isLate = 0;
		$fine = 0;

		if($loginRule)
		{
			$officialTime = strtotime($loginRule['login_time']);
			$grace = $loginRule['grace_period_minutes'] * 60;
			$current = strtotime($timeNow);

			if($current > ($officialTime + $grace) && !$isWeekend && !$isHoliday)
			{
				$isLate = 1;
				$fine = $loginRule['fine_per_day'];
			}
		}

		//Insert Attendance
		$insert = $this->pdo->prepare("INSERT INTO attendance (employee_id, date, check_in, is_late, is_weekend, is_holiday, late_fine) VALUES (?,?,?,?,?,?,?)");

		$insert->execute([$employeeId,$today, $timeNow, $isLate, $isWeekend, $isHoliday, $fine]);

		//Log late fine if needed

		if($isLate && $fine > 0)
		{
			$attendanceId = $this->pdo->lastInsertId();
			$fineLog = $this->pdo->prepare("INSERT INTO late_fines(employee_id, attendance_id, date, fine_amount) VALUES (?,?,?,?)");
			$fineLog->execute([$employeeId, $attendanceId, $today, $fine]);
		}

		return ['success' => true, 'message' => 'Attendance Recorded', 'late' => $isLate, 'fine' => $fine];

	}

	//Check if already marked today
	public function checkTodayAttendance($employeeId)
	{
		$today = date('Y-m-d');

		$stmt = $this->pdo->prepare("SELECT check_in FROM attendance WHERE employee_id = ? AND date = ?");
		$stmt->execute([$employeeId, $today]);

		if($stmt->rowCount() > 0)
		{
			$row = $stmt->fetch();
			return [
					'already_marked' => true,
					'check_in' => $row['check_in'],

					];
		}

		return ['already_marked' => false];
	} 


	public function payrollExists($employeeId, $month, $year)
	{
		$stmt = $this->pdo->prepare("SELECT id FROM payroll WHERE employee_id = ? AND month = ? AND year = ?");

		$stmt->execute([$employeeId, $month, $year]);

		return $stmt->rowCount() > 0;
	}

	public function generatePayroll($employeeId, $month, $year, $bonus = 0, $deduction = 0, $overTimeHours = 0)
	{
		$stmt = $this->pdo->prepare("SELECT basic_salary, overtime_rate FROM salary_structure WHERE employee_id = ?");
		$stmt->execute([$employeeId]);
		$salaryData = $stmt->fetch();

		if(!$salaryData)
		{
			return ['success' => false, 'message' => 'Salary structure not found'];
		}

		$basicSalary = $salaryData['basic_salary'];
		$overtimeRate = $salaryData['overtime_rate'];

		//Count working days and late fines

		$start = "$year-$month-01";
		$end = date("Y-m-t", strtotime($start));

		$attStmt = $this->pdo->prepare("SELECT COUNT(*) as total_present FROM attendance WHERE employee_id = ? AND date BETWEEN ? AND ? AND is_weekend = 0 AND is_holiday = 0");
		$attStmt->execute([$employeeId, $start, $end]);
		$totalPresent = $attStmt->fetch()['total_present'];
	}

	// sum late fines 


}


?>