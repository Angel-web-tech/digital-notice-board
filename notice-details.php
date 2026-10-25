<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$noticeId = filter_input(INPUT_GET, 'notice_id', FILTER_VALIDATE_INT);
$notice = null;
if ($noticeId) {
	try {
		$statement = db()->prepare('SELECT * FROM notices WHERE notice_id = :id LIMIT 1');
		$statement->execute(['id' => $noticeId]);
		$notice = $statement->fetch() ?: null;
		if ($notice && ($notice['status'] ?? 'published') === 'draft' && !can_manage_notices()) $notice = null;
		if ($notice && ($notice['status'] ?? 'published') === 'published') {
			if (empty($_SESSION['viewed_notice_ids']) || !is_array($_SESSION['viewed_notice_ids'])) $_SESSION['viewed_notice_ids'] = [];
			if (!in_array((int) $notice['notice_id'], $_SESSION['viewed_notice_ids'], true)) {
				$increment = db()->prepare('UPDATE notices SET views = views + 1 WHERE notice_id = :id AND status = \'published\'');
				$increment->execute(['id' => $notice['notice_id']]);
				$_SESSION['viewed_notice_ids'][] = (int) $notice['notice_id'];
				$notice['views'] = (int) ($notice['views'] ?? 0) + 1;
			}
		}
	} catch (Throwable $exception) {
		error_log('Notice detail failed: ' . $exception->getMessage());
	}
}
$pageTitle = $notice ? $notice['title'] : 'Notice not found';
require __DIR__ . '/includes/header.php';
?>
<a class="back-link" href="<?= BASE_URL ?>/">← Back to notices</a>
<?php if (!$notice): ?>
	<section class="empty-panel"><span class="empty-mark">!</span><h1>Notice not found</h1><p>This notice may have been removed or the link is invalid.</p></section>
<?php else: $status = notice_status($notice); ?>
	<?php
	$relatedStatement = db()->prepare("SELECT notice_id, title, category, department, publish_date, expiry_date FROM notices WHERE status='published' AND category = :category AND notice_id <> :notice_id AND publish_date <= NOW() AND expiry_date > NOW() ORDER BY is_pinned DESC, publish_date DESC LIMIT 3");
	$relatedStatement->execute(['category' => $notice['category'], 'notice_id' => $notice['notice_id']]);
	$relatedNotices = $relatedStatement->fetchAll();
	?>
	<div class="detail-actions"><button class="button button-light" id="copy-notice-link" type="button">Copy link</button><button class="button button-light" type="button" onclick="window.print()">Print</button><span id="copy-link-feedback" aria-live="polite"></span></div>
	<article class="detail-sheet"><div class="detail-meta"><span class="chip category-chip"><?= h($notice['category']) ?></span><span class="chip department-chip"><?= h($notice['department']) ?></span><span class="status-pill status-<?= h($status) ?>"><?= h(ucfirst($status)) ?></span><?php if ($notice['priority'] === 'urgent' && $status === 'active'): ?><span class="priority-badge">Urgent</span><?php endif; ?></div><h1><?= h($notice['title']) ?></h1><div class="timeline-strip"><div class="timeline-step"><strong>Published</strong><?= h(display_date($notice['publish_date'])) ?></div><div class="timeline-step timeline-now"><strong>Now</strong><?= h(date('d M Y')) ?></div><div class="timeline-step"><strong>Expires</strong><?= h(display_date($notice['expiry_date'])) ?></div></div><p class="detail-dates">Published <?= h(display_date($notice['publish_date'], true)) ?> · Expires <?= h(display_date($notice['expiry_date'], true)) ?> · <?= (int) ($notice['views'] ?? 0) ?> views</p><div class="detail-description"><?= nl2br(h($notice['description'])) ?></div><?php if (!empty($notice['attachment_path'])): ?><section class="attachment-detail"><h2>Attachment</h2><?php $attachmentExt = strtolower(pathinfo($notice['attachment_path'], PATHINFO_EXTENSION)); if (in_array($attachmentExt, ['jpg', 'png'], true)): ?><img class="attachment-preview" src="<?= BASE_URL ?>/api/download-attachment.php?notice_id=<?= (int) $notice['notice_id'] ?>&preview=1" alt="Preview of <?= h($notice['attachment_original_name']) ?>"><?php endif; ?><a class="button button-light" href="<?= BASE_URL ?>/api/download-attachment.php?notice_id=<?= (int) $notice['notice_id'] ?>">Download <?= h($notice['attachment_original_name']) ?></a></section><?php endif; ?><?php if ($status === 'draft'): ?><p class="status-note">Draft preview. Students cannot see this notice.</p><?php elseif ($status === 'scheduled'): ?><p class="status-note">Scheduled for <?= h(display_date($notice['publish_date'], true)) ?>.</p><?php elseif ($status === 'expired'): ?><p class="status-note">This notice has expired and is shown for reference.</p><?php endif; ?></article>
	<?php if ($relatedNotices): ?><section class="related-section"><p class="eyebrow">More from <?= h($notice['category']) ?></p><h2>Related notices</h2><div class="related-list"><?php foreach ($relatedNotices as $related): ?><a class="related-item" href="<?= BASE_URL ?>/notice-details.php?notice_id=<?= (int) $related['notice_id'] ?>"><span><?= h($related['title']) ?></span><small><?= h(display_date($related['publish_date'])) ?></small></a><?php endforeach; ?></div></section><?php endif; ?>
<script>
document.getElementById('copy-notice-link').addEventListener('click', async function () {
	try { await navigator.clipboard.writeText(window.location.href); document.getElementById('copy-link-feedback').textContent = 'Link copied'; }
	catch (error) { document.getElementById('copy-link-feedback').textContent = 'Copy is unavailable in this browser'; }
});
</script>
<?php endif; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
