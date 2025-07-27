<?php
session_start();
header('Content-Type: application/json');
require_once '../../classes/class.admin.php';

$admin = new Admin();

$pdo = $admin->getPdo();

$stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name ASC");
$departments = $stmt->fetchAll();

echo json_encode(['success' => true, 'departments' => $departments]);

?>