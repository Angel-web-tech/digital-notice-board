<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin'], true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Use a POST request.', null, [], 405);
if (!verify_csrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) json_response(false, 'Your session expired. Reload and try again.', null, [], 419);
$id = filter_var($_POST['notice_id'] ?? null, FILTER_VALIDATE_INT);
if (!$id || $id < 1) json_response(false, 'Choose a valid notice.', null, ['notice_id' => 'Notice ID is required.'], 422);
try {
	$pdo = db();
	$pdo->beginTransaction();
	$load = $pdo->prepare('SELECT title, attachment_path FROM notices WHERE notice_id=:id FOR UPDATE');
	$load->execute(['id' => $id]);
	$notice = $load->fetch();
	if (!$notice) { $pdo->rollBack(); json_response(false, 'Notice not found.', null, [], 404); }
	$statement = $pdo->prepare('DELETE FROM notices WHERE notice_id = :id');
	$statement->execute(['id' => $id]);
	log_activity($pdo, 'delete', (int) $id, $notice['title']);
	$pdo->commit();
	remove_attachment_file($notice['attachment_path']);
	json_response(true, 'Notice deleted.');
} catch (Throwable $exception) {
	if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
	error_log('Notice deletion failed: ' . $exception->getMessage());
	json_response(false, 'Notice could not be deleted right now.', null, [], 500);
}
