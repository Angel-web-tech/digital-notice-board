<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin(true);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
	$users = db()->query("SELECT user_id,username,full_name,email,role,status,created_at FROM users ORDER BY (status='pending') DESC,created_at DESC")->fetchAll();
	json_response(true, 'Users loaded.', $users);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Use GET or POST.', null, [], 405);
if (!verify_csrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) json_response(false, 'Your session expired. Reload and try again.', null, [], 419);
$action = (string) ($_POST['action'] ?? 'create');
try {
	$pdo = db();
	if ($action === 'delete') {
		$id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
		if (!$id) json_response(false, 'Choose a valid user.', null, ['user_id' => 'User is required.'], 422);
		if ((int) $id === (int) $_SESSION['user_id']) json_response(false, 'You cannot delete your own account.', null, [], 409);
		$pdo->beginTransaction();
		$target = $pdo->prepare('SELECT user_id, username, role FROM users WHERE user_id=:id FOR UPDATE');
		$target->execute(['id' => $id]);
		$user = $target->fetch();
		if (!$user) { $pdo->rollBack(); json_response(false, 'User not found.', null, [], 404); }
		if ($user['role'] === 'admin') {
			$adminRows = $pdo->query("SELECT user_id FROM users WHERE role='admin' FOR UPDATE")->fetchAll();
			if (count($adminRows) <= 1) { $pdo->rollBack(); json_response(false, 'The last administrator cannot be deleted.', null, [], 409); }
		}
		$delete = $pdo->prepare('DELETE FROM users WHERE user_id=:id');
		$delete->execute(['id' => $id]);
		log_activity($pdo, 'user_delete', null, $user['username']);
		$pdo->commit();
		json_response(true, 'User deleted.');
	}
	if (in_array($action, ['approve','reject','disable','enable','promote'], true)) {
		$id = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
		if (!$id) json_response(false, 'Choose a valid user.', null, [], 422);
		$pdo->beginTransaction();
		$target = $pdo->prepare('SELECT user_id,username,role,status FROM users WHERE user_id=:id FOR UPDATE');
		$target->execute(['id'=>$id]);
		$user = $target->fetch();
		if (!$user) { $pdo->rollBack(); json_response(false, 'User not found.', null, [], 404); }
		if ((int) $id === (int) $_SESSION['user_id'] && in_array($action, ['reject','disable'], true)) { $pdo->rollBack(); json_response(false, 'You cannot disable or reject your own account.', null, [], 409); }
		if ($user['role'] === 'admin' && in_array($action, ['reject','disable','promote'], true)) {
			$admins = $pdo->query("SELECT user_id FROM users WHERE role='admin' FOR UPDATE")->fetchAll();
			if (count($admins) <= 1 || (int) $user['user_id'] === (int) $_SESSION['user_id']) { $pdo->rollBack(); json_response(false, 'You cannot demote or disable yourself or the last administrator.', null, [], 409); }
		}
		[$role,$status] = match ($action) {
			'approve' => [$user['role'],'active'], 'reject' => [$user['role'],'rejected'], 'disable' => [$user['role'],'disabled'], 'enable' => [$user['role'],'active'], 'promote' => ['admin',$user['status']],
		};
		$update = $pdo->prepare('UPDATE users SET role=:role,status=:status WHERE user_id=:id');
		$update->execute(['role'=>$role,'status'=>$status,'id'=>$id]);
		log_activity($pdo, 'user_' . $action, null, $user['username']);
		$pdo->commit();
		json_response(true, 'Account updated.');
	}
	$username = trim((string) ($_POST['username'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$confirm = (string) ($_POST['confirm_password'] ?? '');
	$role = (string) ($_POST['role'] ?? '');
	$errors = [];
	if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) $errors['username'] = 'Use 3 to 30 letters, numbers or underscores.';
	if (!preg_match('/^(?=.*[A-Za-z])(?=.*[0-9]).{8,}$/', $password)) $errors['password'] = 'Use at least 8 characters, including a letter and a number.';
	if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
	if (!in_array($role, ['admin', 'faculty'], true)) $errors['role'] = 'Choose Admin or Faculty.';
	$exists = $pdo->prepare('SELECT 1 FROM users WHERE username=:username');
	$exists->execute(['username' => $username]);
	if ($exists->fetchColumn()) $errors['username'] = 'That username is already in use.';
	if ($errors) json_response(false, 'Please correct the highlighted fields.', null, $errors, 422);
	$pdo->beginTransaction();
	$insert = $pdo->prepare("INSERT INTO users (username, password_hash, role, status) VALUES (:username, :hash, :role, 'active')");
	$insert->execute(['username' => $username, 'hash' => password_hash($password, PASSWORD_DEFAULT), 'role' => $role]);
	log_activity($pdo, 'user_create', null, $username);
	$createdId = (int) $pdo->lastInsertId();
	$pdo->commit();
	json_response(true, 'User created successfully.', ['user_id' => $createdId]);
} catch (Throwable $exception) {
	if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
	error_log('User management failed: ' . $exception->getMessage());
	json_response(false, 'User management is temporarily unavailable.', null, [], 500);
}