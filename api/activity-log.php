<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin(true);
$page = max(1, min(10000, (int) ($_GET['page'] ?? 1)));
$perPage = 20;
$action = trim((string) ($_GET['action'] ?? ''));
$username = trim((string) ($_GET['user'] ?? ''));
$where = [];
$params = [];
if ($action !== '') { $where[] = 'action=:action'; $params['action'] = $action; }
if ($username !== '') { $where[] = 'username=:username'; $params['username'] = $username; }
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
try {
	$pdo = db();
	$count = $pdo->prepare('SELECT COUNT(*) FROM activity_log' . $whereSql);
	$count->execute($params);
	$total = (int) $count->fetchColumn();
	$query = $pdo->prepare('SELECT id, username, action, notice_id, notice_title, created_at FROM activity_log' . $whereSql . ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset');
	foreach ($params as $key => $value) $query->bindValue(':' . $key, $value, PDO::PARAM_STR);
	$query->bindValue(':limit', $perPage, PDO::PARAM_INT);
	$query->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
	$query->execute();
	json_response(true, 'Activity loaded.', ['items' => $query->fetchAll(), 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'has_more' => $page * $perPage < $total]]);
} catch (Throwable $exception) {
	error_log('Activity log query failed: ' . $exception->getMessage());
	json_response(false, 'Activity is temporarily unavailable.', null, [], 500);
}