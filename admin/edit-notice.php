<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'faculty']);
$noticeId = filter_input(INPUT_GET, 'notice_id', FILTER_VALIDATE_INT);
$statement = $noticeId ? db()->prepare('SELECT * FROM notices WHERE notice_id=:id') : null;
if ($statement) $statement->execute(['id' => $noticeId]);
$notice = $statement ? $statement->fetch() : false;
if (!$notice) {
	http_response_code(404);
	$notice = ['notice_id' => '', 'title' => '', 'description' => '', 'category' => NOTICE_CATEGORIES[0], 'department' => NOTICE_DEPARTMENTS[0], 'priority' => 'normal', 'status' => 'published', 'is_pinned' => 0, 'publish_date' => date('Y-m-d H:i:s'), 'expiry_date' => date('Y-m-d H:i:s', strtotime('+30 days')), 'attachment_path' => null, 'attachment_original_name' => null];
	$formTitle = 'Notice not found';
} else { $formTitle = 'Edit notice'; }
$notice['publish_date'] = date('Y-m-d\TH:i', strtotime($notice['publish_date']));
$notice['expiry_date'] = date('Y-m-d\TH:i', strtotime($notice['expiry_date']));
$pageTitle = $formTitle;
require __DIR__ . '/../includes/header.php';
?>
<section class="admin-shell"><aside class="admin-sidebar"><a href="<?= BASE_URL ?>/admin/dashboard.php">Overview</a><a class="active" href="<?= BASE_URL ?>/admin/manage-notices.php">Manage notices</a><a href="<?= BASE_URL ?>/admin/add-notice.php">Add notice</a></aside><div class="admin-content"><div class="admin-heading"><div><p class="eyebrow">Notice management</p><h1><?= h($formTitle) ?></h1></div><a class="button button-light" href="<?= BASE_URL ?>/admin/manage-notices.php">← All notices</a></div><?php if ($notice['notice_id']): ?><div id="toast-region" class="toast-region" aria-live="polite"></div><?php require __DIR__ . '/notice-form.php'; ?><script src="<?= BASE_URL ?>/assets/js/description-validation.js?v=20261024-ui"></script><script src="<?= BASE_URL ?>/assets/js/admin.js?v=20261024-ui"></script><?php else: ?><p class="empty-panel">The requested notice does not exist.</p><?php endif; ?></div></section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
