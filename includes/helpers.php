<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Kolkata');

define('BASE_URL', '/digital-notice-board');
define('APP_TIMEZONE', 'Asia/Kolkata');

const NOTICE_CATEGORIES = [
	'Academics', 'Examinations', 'Events', 'Internships', 'Placements', 'Workshops', 'General Announcements',
];
const NOTICE_DEPARTMENTS = ['All Departments', 'CSE', 'ECE', 'EEE', 'Mechanical', 'Civil'];
const NOTICE_PRIORITIES = ['normal', 'urgent'];

function h(mixed $value): string
{
	return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function display_date(string $value, bool $includeTime = false): string
{
	$date = new DateTimeImmutable($value, new DateTimeZone(APP_TIMEZONE));
	return $date->format('d M Y') . ($includeTime ? $date->format(', g:i a') : '');
}

function notice_countdown_label(int $days): string
{
	return 'Ends in ' . max(0, $days) . (max(0, $days) === 1 ? ' day' : ' days');
}

function start_secure_session(): void
{
	if (session_status() === PHP_SESSION_ACTIVE) {
		return;
	}
	session_set_cookie_params([
		'lifetime' => 0,
		'path' => BASE_URL . '/',
		'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
		'httponly' => true,
		'samesite' => 'Lax',
	]);
	session_start();
}

function csrf_token(): string
{
	start_secure_session();
	if (empty($_SESSION['csrf_token'])) {
		$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
	}
	return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
	start_secure_session();
	return is_string($token) && isset($_SESSION['csrf_token'])
		&& hash_equals($_SESSION['csrf_token'], $token);
}

function json_response(bool $success, string $message, mixed $data = null, array $errors = [], int $status = 200): never
{
	http_response_code($status);
	header('Content-Type: application/json; charset=utf-8');
	header('Cache-Control: no-store');
	echo json_encode([
		'success' => $success,
		'message' => $message,
		'data' => $data,
		'errors' => (object) $errors,
	], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	exit;
}

function status_sql(string $status, string $alias = 'n'): array
{
	$prefix = $alias === '' ? '' : $alias . '.';
	return match ($status) {
		'draft' => ["{$prefix}status = 'draft'", []],
		'active' => ["{$prefix}status = 'published' AND {$prefix}publish_date <= NOW() AND {$prefix}expiry_date > NOW()", []],
		'scheduled' => ["{$prefix}status = 'published' AND {$prefix}publish_date > NOW()", []],
		'expired', 'archived' => ["{$prefix}status = 'published' AND {$prefix}expiry_date <= NOW()", []],
		'urgent' => ["{$prefix}status = 'published' AND {$prefix}priority = 'urgent' AND {$prefix}publish_date <= NOW() AND {$prefix}expiry_date > NOW()", []],
		default => ['1 = 1', []],
	};
}

function status_case_sql(string $alias = 'n'): string
{
	[$expired] = status_sql('expired', $alias);
	[$scheduled] = status_sql('scheduled', $alias);
	return "CASE WHEN {$expired} THEN 'expired' WHEN {$scheduled} THEN 'scheduled' ELSE 'active' END";
}

function notice_status(array $notice, ?DateTimeImmutable $now = null): string
{
	if (($notice['status'] ?? 'published') === 'draft') {
		return 'draft';
	}
	$timezone = new DateTimeZone(APP_TIMEZONE);
	$now ??= new DateTimeImmutable('now', $timezone);
	$publish = new DateTimeImmutable((string) $notice['publish_date'], $timezone);
	$expiry = new DateTimeImmutable((string) $notice['expiry_date'], $timezone);
	if ($expiry <= $now) {
		return 'expired';
	}
	return $publish > $now ? 'scheduled' : 'active';
}

function log_activity(PDO $pdo, string $action, ?int $noticeId = null, ?string $noticeTitle = null): void
{
	$statement = $pdo->prepare('INSERT INTO activity_log (user_id, username, action, notice_id, notice_title) VALUES (:user_id, :username, :action, :notice_id, :notice_title)');
	$statement->execute([
		'user_id' => $_SESSION['user_id'] ?? null,
		'username' => (string) ($_SESSION['username'] ?? 'unknown'),
		'action' => $action,
		'notice_id' => $noticeId,
		'notice_title' => $noticeTitle,
	]);
}

function log_named_activity(PDO $pdo, string $username, string $action, ?int $userId = null): void
{
	$statement = $pdo->prepare('INSERT INTO activity_log (user_id, username, action) VALUES (:user_id, :username, :action)');
	$statement->execute(['user_id' => $userId, 'username' => mb_substr($username, 0, 30), 'action' => $action]);
}

function safe_upload_name(string $extension): string
{
	return bin2hex(random_bytes(24)) . '.' . strtolower($extension);
}

function validate_attachment(array $file): array
{
	if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) return ['error' => null, 'present' => false];
	if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) return ['error' => 'The attachment could not be uploaded.'];
	if ((int) ($file['size'] ?? 0) > 2 * 1024 * 1024) return ['error' => 'Attachments must be 2 MB or smaller.'];
	$originalName = basename((string) ($file['name'] ?? ''));
	$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
	// Trust neither the filename nor browser MIME; both extension and detected content must match.
	$mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) ($file['tmp_name'] ?? ''));
	$allowed = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
	if (!isset($allowed[$extension]) || $allowed[$extension] !== $mime) return ['error' => 'Choose a genuine PDF, JPG, or PNG file.'];
	return ['error' => null, 'present' => true, 'extension' => $extension === 'jpeg' ? 'jpg' : $extension, 'mime' => $mime, 'original_name' => $originalName];
}

function store_attachment(array $file, string $extension): string
{
	$directory = dirname(__DIR__) . '/uploads';
	if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
		throw new RuntimeException('Attachment storage is unavailable.');
	}
	$filename = safe_upload_name($extension);
	if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $filename)) {
		throw new RuntimeException('Attachment storage is unavailable.');
	}
	return $filename;
}

function remove_attachment_file(?string $filename): void
{
	if (!$filename || basename($filename) !== $filename) return;
	$path = dirname(__DIR__) . '/uploads/' . $filename;
	if (is_file($path)) @unlink($path);
}

function account_config(): array
{
	$path = __DIR__ . '/../config/app.php';
	return is_file($path) ? (require $path) : ['base_url' => 'http://localhost' . BASE_URL, 'staff_registration_code' => ''];
}

function valid_full_name(string $name): bool
{
	$length = mb_strlen(trim($name));
	return $length >= 3 && $length <= 60 && preg_match('/^[\p{L} .]+$/u', trim($name)) === 1;
}

function valid_staff_password(string $password): bool
{
	return strlen($password) >= 8 && preg_match('/[A-Za-z]/', $password) === 1 && preg_match('/[0-9]/', $password) === 1;
}

function ip_rate_limit(PDO $pdo, string $purpose, string $ip, int $maximum, int $windowSeconds, bool $perIp = true): bool
{
	$sql = 'SELECT COUNT(*) FROM login_attempts WHERE username=:purpose AND attempted_at >= DATE_SUB(NOW(), INTERVAL :window SECOND)' . ($perIp ? ' AND ip_address=:ip' : '');
	$statement = $pdo->prepare($sql);
	$statement->bindValue(':purpose', mb_substr('__' . $purpose, 0, 30), PDO::PARAM_STR);
	if ($perIp) $statement->bindValue(':ip', mb_substr($ip, 0, 45), PDO::PARAM_STR);
	$statement->bindValue(':window', $windowSeconds, PDO::PARAM_INT);
	$statement->execute();
	return (int) $statement->fetchColumn() < $maximum;
}

function record_ip_rate_limit(PDO $pdo, string $purpose, string $ip): void
{
	$statement = $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at, was_successful) VALUES (:purpose, :ip, NOW(), 0)');
	$statement->execute(['purpose' => mb_substr('__' . $purpose, 0, 30), 'ip' => mb_substr($ip, 0, 45)]);
}

function notice_is_new(array $notice, ?DateTimeImmutable $now = null): bool
{
	$timezone = new DateTimeZone(APP_TIMEZONE);
	$now ??= new DateTimeImmutable('now', $timezone);
	if (notice_status($notice, $now) !== 'active') {
		return false;
	}
	$published = new DateTimeImmutable((string) $notice['publish_date'], $timezone);
	return $published >= $now->modify('-24 hours') && $published <= $now;
}

function validate_notice_input(array $input, bool $isUpdate = false): array
{
	$errors = [];
	$title = trim((string) ($input['title'] ?? ''));
	$description = trim((string) ($input['description'] ?? ''));
	if (mb_strlen($title) < 5 || mb_strlen($title) > 150) {
		$errors['title'] = 'Title must be between 5 and 150 characters.';
	}
	if (mb_strlen($description) < 10 || mb_strlen($description) > 2000) {
		$errors['description'] = 'Description must be between 10 and 2000 characters.';
	}
	if (!in_array($input['category'] ?? '', NOTICE_CATEGORIES, true)) {
		$errors['category'] = 'Choose a valid category.';
	}
	if (!in_array($input['department'] ?? '', NOTICE_DEPARTMENTS, true)) {
		$errors['department'] = 'Choose a valid department.';
	}
	if (!in_array($input['priority'] ?? '', NOTICE_PRIORITIES, true)) {
		$errors['priority'] = 'Choose a valid priority.';
	}
	$publish = parse_notice_datetime((string) ($input['publish_date'] ?? ''));
	$expiry = parse_notice_datetime((string) ($input['expiry_date'] ?? ''));
	if (!$publish) $errors['publish_date'] = 'Enter a valid publish date.';
	if (!$expiry) $errors['expiry_date'] = 'Enter a valid expiry date.';
	if ($publish && $expiry && $expiry <= $publish) {
		$errors['expiry_date'] = 'Expiry must be after the publish date.';
	}
	if (!$isUpdate && $expiry && $expiry <= new DateTimeImmutable('now', new DateTimeZone(APP_TIMEZONE))) {
		$errors['expiry_date'] = 'Expiry date must be in the future for new notices.';
	}
	return $errors;
}

function parse_notice_datetime(string $value): ?DateTimeImmutable
{
	$timezone = new DateTimeZone(APP_TIMEZONE);
	foreach (['!Y-m-d\TH:i', '!Y-m-d H:i:s', '!Y-m-d H:i'] as $format) {
		$date = DateTimeImmutable::createFromFormat($format, $value, $timezone);
		$issues = DateTimeImmutable::getLastErrors();
		if ($date && ($issues === false || ($issues['warning_count'] === 0 && $issues['error_count'] === 0))) {
			return $date;
		}
	}
	return null;
}
