<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'faculty'], true);
try {
	$pdo = db();
	[$activeSql] = status_sql('active');
	[$scheduledSql] = status_sql('scheduled');
	[$expiredSql] = status_sql('expired');
	[$urgentSql] = status_sql('urgent');
	$counts = $pdo->query("SELECT SUM(status='published') AS total, SUM({$activeSql}) AS active, SUM({$scheduledSql}) AS scheduled, SUM({$expiredSql}) AS expired, SUM({$urgentSql}) AS urgent FROM notices n")->fetch();
	$categories = $pdo->query("SELECT category, COUNT(*) AS total FROM notices WHERE status='published' GROUP BY category ORDER BY total DESC, category")->fetchAll();
	$departments = $pdo->query("SELECT department, COUNT(*) AS total FROM notices WHERE status='published' GROUP BY department ORDER BY total DESC, department")->fetchAll();
	$recent = $pdo->query('SELECT n.notice_id, n.title, n.category, n.department, n.priority, n.publish_date, n.expiry_date, ' . status_case_sql() . ' AS status FROM notices n WHERE n.status=\'published\' ORDER BY n.created_at DESC LIMIT 6')->fetchAll();
	$urgentQuery = $pdo->query("SELECT notice_id, title, department, expiry_date FROM notices WHERE status='published' AND priority='urgent' AND publish_date <= NOW() AND expiry_date > NOW() ORDER BY expiry_date ASC LIMIT 6")->fetchAll();
	$monthly = $pdo->query("SELECT DATE_FORMAT(publish_date, '%Y-%m') AS month_key, COUNT(*) AS total FROM notices WHERE status='published' AND publish_date >= DATE_SUB(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 11 MONTH) AND publish_date < DATE_ADD(DATE_FORMAT(NOW(), '%Y-%m-01'), INTERVAL 1 MONTH) GROUP BY DATE_FORMAT(publish_date, '%Y-%m') ORDER BY month_key")->fetchAll();
	$mostViewed = $pdo->query("SELECT notice_id,title,category,views FROM notices WHERE status='published' ORDER BY views DESC,created_at DESC LIMIT 5")->fetchAll();
	json_response(true, 'Dashboard statistics loaded.', ['counts' => array_map('intval', $counts), 'categories' => $categories, 'departments' => $departments, 'recent' => $recent, 'urgent' => $urgentQuery, 'monthly' => $monthly, 'most_viewed' => $mostViewed]);
} catch (Throwable $exception) {
	error_log('Dashboard statistics failed: ' . $exception->getMessage());
	json_response(false, 'Dashboard statistics are temporarily unavailable.', null, [], 500);
}
