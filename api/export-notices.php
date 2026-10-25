<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin'], true);
$search = mb_substr(trim((string) ($_GET['search'] ?? '')), 0, 100);
$category = (string) ($_GET['category'] ?? '');
$department = (string) ($_GET['department'] ?? '');
$status = (string) ($_GET['status'] ?? '');
$where = [];
$params = [];
if ($search !== '') { $where[] = '(title LIKE :title_search OR description LIKE :description_search)'; $params['title_search'] = '%' . $search . '%'; $params['description_search'] = '%' . $search . '%'; }
if (in_array($category, NOTICE_CATEGORIES, true)) { $where[] = 'category=:category'; $params['category'] = $category; }
if (in_array($department, NOTICE_DEPARTMENTS, true)) { $where[] = 'department=:department'; $params['department'] = $department; }
if (in_array($status, ['active','scheduled','expired','draft'], true)) { [$predicate] = status_sql($status, ''); $where[] = $predicate; }
$query = db()->prepare('SELECT notice_id,title,description,category,department,priority,status,publish_date,expiry_date,views FROM notices' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY created_at DESC');
$query->execute($params);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="digital-notices-' . date('Ymd') . '.csv"');
header('Cache-Control: no-store');
$output = fopen('php://output', 'wb');
fwrite($output, chr(239) . chr(187) . chr(191));
fputcsv($output, ['ID','Title','Description','Category','Department','Priority','Status','Publish date','Expiry date','Views']);
foreach ($query as $row) {
    foreach ($row as &$cell) {
        $cell = (string) $cell;
        if (preg_match('/^[\s]*[=+\-@]/u', $cell)) $cell = "'" . $cell;
    }
    unset($cell);
    fputcsv($output, $row);
}
fclose($output);
