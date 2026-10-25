<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
$noticeId = filter_input(INPUT_GET, 'notice_id', FILTER_VALIDATE_INT);
if (!$noticeId) { http_response_code(404); exit; }
$statement = db()->prepare('SELECT status, attachment_path, attachment_original_name FROM notices WHERE notice_id=:id LIMIT 1');
$statement->execute(['id' => $noticeId]);
$notice = $statement->fetch();
if (!$notice || ($notice['status'] === 'draft' && !can_manage_notices()) || !$notice['attachment_path'] || basename($notice['attachment_path']) !== $notice['attachment_path']) { http_response_code(404); exit; }
$path = dirname(__DIR__) . '/uploads/' . $notice['attachment_path'];
if (!is_file($path)) { http_response_code(404); exit; }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) { http_response_code(404); exit; }
$preview = isset($_GET['preview']) && $_GET['preview'] === '1' && str_starts_with($mime, 'image/');
header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Content-Disposition: ' . ($preview ? 'inline' : 'attachment') . '; filename="' . rawurlencode((string) $notice['attachment_original_name']) . '"');
header('Content-Length: ' . filesize($path));
readfile($path);