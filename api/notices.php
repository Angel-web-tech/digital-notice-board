<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
	json_response(false, 'Use a GET request.', null, [], 405);
}

$search = trim((string) ($_GET['search'] ?? ''));
if (mb_strlen($search) > 100) {
	$search = mb_substr($search, 0, 100);
}
$category = (string) ($_GET['category'] ?? '');
$department = (string) ($_GET['department'] ?? '');
$mode = (string) ($_GET['status'] ?? 'active');
$sort = (string) ($_GET['sort'] ?? 'newest');
$page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
$page = $page && $page > 0 ? min($page, 10000) : 1;
$perPage = 9;
$where = [];
$params = [];

if ($mode === 'archived') {
	[$statusSql] = status_sql('archived');
	$where[] = $statusSql;
} elseif ($mode === 'all') {
	[$statusSql] = status_sql('active');
	[$scheduledSql] = status_sql('scheduled');
	$where[] = "({$statusSql} OR {$scheduledSql})";
} else {
	[$statusSql] = status_sql('active');
	$where[] = $statusSql;
}
if ($search !== '') {
	$where[] = '(n.title LIKE :search_title OR n.description LIKE :search_description OR n.department LIKE :search_department)';
	$params['search_title'] = '%' . $search . '%';
	$params['search_description'] = '%' . $search . '%';
	$params['search_department'] = '%' . $search . '%';
}
if (in_array($category, NOTICE_CATEGORIES, true)) {
	$where[] = 'n.category = :category';
	$params['category'] = $category;
}
if (in_array($department, NOTICE_DEPARTMENTS, true) && $department !== 'All Departments') {
	$where[] = '(n.department = :department OR n.department = \'All Departments\')';
	$params['department'] = $department;
}
$orderBy = match ($sort) {
	'oldest' => 'n.is_pinned DESC, n.publish_date ASC',
	'expiry' => 'n.is_pinned DESC, n.expiry_date ASC',
	default => 'n.is_pinned DESC, n.publish_date DESC',
};

try {
	$pdo = db();
	$whereSql = implode(' AND ', $where);
	$countStatement = $pdo->prepare('SELECT COUNT(*) FROM notices n WHERE ' . $whereSql);
	$countStatement->execute($params);
	$total = (int) $countStatement->fetchColumn();
	$statement = $pdo->prepare('SELECT n.notice_id, n.title, n.description, n.category, n.department, n.priority, n.status, n.is_pinned, n.publish_date, n.expiry_date, n.created_at FROM notices n WHERE ' . $whereSql . ' ORDER BY ' . $orderBy . ' LIMIT :limit OFFSET :offset');
	foreach ($params as $key => $value) $statement->bindValue(':' . $key, $value, PDO::PARAM_STR);
	$statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
	$statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
	$statement->execute();
	$notices = $statement->fetchAll();
	foreach ($notices as &$notice) {
		$notice['status'] = notice_status($notice);
		$notice['is_new'] = notice_is_new($notice);
		$days = (int) ceil((strtotime((string) $notice['expiry_date']) - time()) / 86400);
		$notice['expires_in_days'] = $days >= 0 ? $days : null;
	}
	unset($notice);
	$urgent = [];
	$expiringSoon = [];
	$heroStats = ['active' => 0, 'urgent' => 0];
	if ($mode === 'active' && $page === 1) {
		$urgent = $pdo->query("SELECT notice_id, title FROM notices WHERE status='published' AND priority='urgent' AND publish_date <= NOW() AND expiry_date > NOW() ORDER BY is_pinned DESC, expiry_date ASC LIMIT 20")->fetchAll();
		$heroStats = $pdo->query("SELECT SUM(status='published' AND publish_date <= NOW() AND expiry_date > NOW()) AS active, SUM(status='published' AND priority='urgent' AND publish_date <= NOW() AND expiry_date > NOW()) AS urgent FROM notices")->fetch();
		[$activePredicate] = status_sql('active');
		$expiringSoon = $pdo->query('SELECT n.notice_id, n.title, n.description, n.category, n.department, n.priority, n.status, n.is_pinned, n.publish_date, n.expiry_date, n.created_at FROM notices n WHERE ' . $activePredicate . ' AND n.expiry_date <= DATE_ADD(NOW(), INTERVAL 3 DAY) ORDER BY n.expiry_date ASC LIMIT 9')->fetchAll();
		foreach ($expiringSoon as &$notice) {
			$notice['status'] = 'active';
			$notice['is_new'] = notice_is_new($notice);
			$notice['expires_in_days'] = max(0, (int) ceil((strtotime((string) $notice['expiry_date']) - time()) / 86400));
		}
		unset($notice);
	}
	json_response(true, 'Notices loaded.', ['notices' => $notices, 'urgent' => $urgent, 'expiring_soon' => $expiringSoon, 'hero_stats' => $heroStats, 'pagination' => ['page' => $page, 'per_page' => $perPage, 'total' => $total, 'has_more' => $page * $perPage < $total]]);
} catch (Throwable $exception) {
	error_log('Notice list failed: ' . $exception->getMessage());
	json_response(false, 'Notices could not be loaded right now.', null, [], 500);
}
