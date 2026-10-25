<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
start_secure_session();

function is_admin(): bool
{
	return isset($_SESSION['user_id']) && ($_SESSION['role'] ?? '') === 'admin';
}

function current_role(): string
{
	return (string) ($_SESSION['role'] ?? '');
}

function can_manage_users(): bool
{
	return is_admin();
}

function can_view_activity(): bool
{
	return is_admin();
}

function can_export_notices(): bool
{
	return is_admin();
}

function can_delete_notices(): bool
{
	return is_admin();
}

function can_manage_notices(): bool
{
	return is_admin() || current_role() === 'faculty';
}

function require_role(array $roles, bool $json = false): void
{
	if (!isset($_SESSION['user_id'])) {
		if ($json) json_response(false, 'Please sign in to continue.', null, [], 401);
		header('Location: ' . BASE_URL . '/login.php');
		exit;
	}
	if (!empty($_SESSION['must_change_password']) && basename($_SERVER['PHP_SELF'] ?? '') !== 'change-password.php') {
		if ($json) json_response(false, 'Change your password before continuing.', null, [], 403);
		header('Location: ' . BASE_URL . '/admin/change-password.php?required=1');
		exit;
	}
	if (in_array(current_role(), $roles, true)) return;
	if ($json) json_response(false, 'You do not have permission to perform this action.', null, [], 403);
	http_response_code(403);
	$pageTitle = 'Access denied';
	require __DIR__ . '/../access-denied.php';
	exit;
}

function require_admin(bool $json = false): void
{
	require_role(['admin'], $json);
}
