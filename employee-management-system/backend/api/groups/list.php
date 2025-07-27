<?php
session_start();
header('Content-Type: application/json');

require_once '../../classes/class.admin.php';

$admin = new Admin();

if(!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin')
{
	echo json_encode(['success' => false, 'message' => 'Unauthorized']);
	exit;
}

try 
{
	$pdo = $admin->getPdo();

	$stmt = $pdo->query("

				SELECT
					g.id,
					g.group_name,
					g.description,
					g.created_by,
					u.username AS created_by_username,
					g.created_at
				FROM groups g
				JOIN users u ON g.created_by = u.id
				ORDER BY g.created_at DESC
			");
	$groups = $stmt->fetchAll();

	$result = [];

	foreach ($groups as $group) 
	{	
		$memberStmt = $pdo->prepare("
					   SELECT e.id, e.name, e.email
					   FROM group_members gm 
					   JOIN employees e ON gm.employee_id = e.id
					   WHERE gm.group_id = ?
			");

		$memberStmt->execute([$group['id']]);
		$members = $memberStmt->fetchAll();

		$result[] = [

				'group_id' => $group['id'],
				'group_name' => $group['group_name'],
				'description' => $group['description'],
				'created_by' => $group['created_by_username'],
				'created_at' => $group['created_at'],
				'members' => $members
		];
	}

	echo json_encode(['success' => true, 'groups' => $result]);
} 
catch (Exception $e) 
{
	echo json_encode(['success' => false, 'message' => 'Failed to fetch group list', 'error' => $e->getMessage()]);
}

?>