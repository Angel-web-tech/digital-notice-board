<?php
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin', 'faculty']);
$notice = ['notice_id' => '', 'title' => '', 'description' => '', 'category' => NOTICE_CATEGORIES[0], 'department' => NOTICE_DEPARTMENTS[0], 'priority' => 'normal', 'status' => 'published', 'is_pinned' => 0, 'publish_date' => date('Y-m-d\TH:i'), 'expiry_date' => date('Y-m-d\TH:i', strtotime('+30 days')), 'attachment_path' => null, 'attachment_original_name' => null];
$formTitle = 'Add notice';
$pageTitle = $formTitle;
require __DIR__ . '/../includes/header.php';
?>
<section class="admin-shell"><aside class="admin-sidebar"><a href="<?= BASE_URL ?>/admin/dashboard.php">Overview</a><a href="<?= BASE_URL ?>/admin/manage-notices.php">Manage notices</a><a class="active" href="<?= BASE_URL ?>/admin/add-notice.php">Add notice</a></aside><div class="admin-content"><div class="admin-heading"><div><p class="eyebrow">Notice management</p><h1><?= h($formTitle) ?></h1></div><a class="button button-light" href="<?= BASE_URL ?>/admin/manage-notices.php">← All notices</a></div><div id="toast-region" class="toast-region" aria-live="polite"></div><?php require __DIR__ . '/notice-form.php'; ?></div></section>
<script src="<?= BASE_URL ?>/assets/js/description-validation.js?v=20261024-ui"></script><script src="<?= BASE_URL ?>/assets/js/admin.js?v=20261024-ui"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>

