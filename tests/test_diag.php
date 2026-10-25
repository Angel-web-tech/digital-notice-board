<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'faculty'], true);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Use a POST request.', null, [], 405);
if (!verify_csrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) json_response(false, 'Your session expired. Reload and try again.', null, [], 419);
$rawId = trim((string) ($_POST['notice_id'] ?? ''));
$id = $rawId === '' ? null : filter_var($rawId, FILTER_VALIDATE_INT);
if ($rawId !== '' && ($id === false || $id < 1)) json_response(false, 'Notice ID is invalid.', null, ['notice_id' => 'Notice ID is invalid.'], 422);
$isUpdate = $id !== null;
$errors = validate_notice_input($_POST, $isUpdate);
$status = (string) ($_POST['status'] ?? 'published');
if (!in_array($status, ['draft', 'published'], true)) $errors['status'] = 'Choose Draft or Published.';
$uploadInfo = null;
if (isset($_FILES['attachment'])) {
	$uploadInfo = validate_attachment($_FILES['attachment']);
	if ($uploadInfo['error']) $errors['attachment'] = $uploadInfo['error'];
}
if ($errors) json_response(false, 'Please correct the highlighted fields.', null, $errors, 422);
$values = [
	'title' => trim((string) $_POST['title']), 'description' => trim((string) $_POST['description']),
	'category' => (string) $_POST['category'], 'department' => (string) $_POST['department'],
	'priority' => (string) $_POST['priority'], 'is_pinned' => isset($_POST['is_pinned']) ? 1 : 0,
	'publish_date' => str_replace('T', ' ', (string) $_POST['publish_date']) . (strlen((string) $_POST['publish_date']) === 16 ? ':00' : ''),
	'expiry_date' => str_replace('T', ' ', (string) $_POST['expiry_date']) . (strlen((string) $_POST['expiry_date']) === 16 ? ':00' : ''),
	'status' => $status,
];
$oldAttachment = null;
$newAttachment = null;
$removeAttachment = isset($_POST['remove_attachment']) && $_POST['remove_attachment'] === '1';
try {
	$pdo = db();
	$pdo->beginTransaction();
	if ($isUpdate) {
		$current = $pdo->prepare('SELECT attachment_path, title FROM notices WHERE notice_id=:id FOR UPDATE');
		$current->execute(['id' => $id]);
		$currentNotice = $current->fetch();
		if (!$currentNotice) { $pdo->rollBack(); json_response(false, 'Notice not found.', null, [], 404); }
		$oldAttachment = $currentNotice['attachment_path'];
		if ($uploadInfo && $uploadInfo['present'] && !$uploadInfo['error']) $newAttachment = store_attachment($_FILES['attachment'], $uploadInfo['extension']);
		$attachmentPath = $newAttachment ?? ($removeAttachment ? null : $oldAttachment);
		$attachmentOriginal = $newAttachment ? $uploadInfo['original_name'] : null;
		if (!$newAttachment && !$removeAttachment) {
			$original = $pdo->prepare('SELECT attachment_original_name FROM notices WHERE notice_id=:id');
			$original->execute(['id' => $id]);
			$attachmentOriginal = $original->fetchColumn() ?: null;
		}
		$values['notice_id'] = $id;
		$values['attachment_path'] = $attachmentPath;
		$values['attachment_original_name'] = $attachmentOriginal;
		$statement = $pdo->prepare('UPDATE notices SET title=:title, description=:description, category=:category, department=:department, priority=:priority, is_pinned=:is_pinned, publish_date=:publish_date, expiry_date=:expiry_date, status=:status, attachment_path=:attachment_path, attachment_original_name=:attachment_original_name WHERE notice_id=:notice_id');
		$statement->execute($values);
		$exists = $pdo->prepare('SELECT 1 FROM notices WHERE notice_id=:id');
		$exists->execute(['id' => $id]);
		if (!$exists->fetchColumn()) { $pdo->rollBack(); json_response(false, 'Notice not found.', null, [], 404); }
		$savedId = $id; $message = 'Notice updated successfully.';
		log_activity($pdo, 'edit', $id, $values['title']);
	} else {
		if ($uploadInfo && $uploadInfo['present'] && !$uploadInfo['error']) $newAttachment = store_attachment($_FILES['attachment'], $uploadInfo['extension']);
		$values['created_by'] = (int) $_SESSION['user_id'];
		$values['attachment_path'] = $newAttachment;
		$values['attachment_original_name'] = $newAttachment ? $uploadInfo['original_name'] : null;
		$statement = $pdo->prepare('INSERT INTO notices (title, description, category, department, priority, is_pinned, publish_date, expiry_date, status, attachment_path, attachment_original_name, created_by) VALUES (:title, :description, :category, :department, :priority, :is_pinned, :publish_date, :expiry_date, :status, :attachment_path, :attachment_original_name, :created_by)');
		$statement->execute($values);
		$savedId = (int) $pdo->lastInsertId(); $message = $status === 'draft' ? 'Draft saved successfully.' : 'Notice published successfully.';
		log_activity($pdo, 'add', $savedId, $values['title']);
	}
	$pdo->commit();
	if ($oldAttachment && ($newAttachment || $removeAttachment)) remove_attachment_file($oldAttachment);
	json_response(true, $message, ['notice_id' => $savedId]);
} catch (Throwable $exception) {
	if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
	if ($newAttachment) remove_attachment_file($newAttachment);
	error_log('Notice save failed: ' . $exception->getMessage());
	json_response(false, 'Notice could not be saved right now.', null, [], 500);
}
