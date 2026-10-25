<?php
require_once __DIR__ . '/includes/helpers.php';
$pageTitle = 'Archived Notices';
$boardStatus = 'archived';
require __DIR__ . '/includes/header.php';
?>
<section class="compact-hero"><p class="eyebrow">Notice archive</p><h1>Past announcements</h1><p>Expired notices remain available here for reference.</p></section>
<section class="board-section" aria-labelledby="archive-heading">
	<div class="section-heading"><div><p class="eyebrow">Archive</p><h2 id="archive-heading">Expired notices</h2></div><span class="result-count" id="result-count">Loading notices</span></div>
	<form id="notice-filters" class="filter-bar" autocomplete="off">
		<label class="search-field"><span class="sr-only">Search archived notices</span><span aria-hidden="true">⌕</span><input id="search" name="search" type="search" maxlength="100" placeholder="Search archive..."></label>
		<label><span class="sr-only">Category</span><select name="category"><option value="">All categories</option><?php foreach (NOTICE_CATEGORIES as $category): ?><option value="<?= h($category) ?>"><?= h($category) ?></option><?php endforeach; ?></select></label>
		<label><span class="sr-only">Department</span><select name="department"><option value="">All departments</option><?php foreach (array_slice(NOTICE_DEPARTMENTS, 1) as $department): ?><option value="<?= h($department) ?>"><?= h($department) ?></option><?php endforeach; ?></select></label>
		<label><span class="sr-only">Sort notices</span><select name="sort"><option value="newest">Newest first</option><option value="oldest">Oldest first</option><option value="expiry">Most recently expired</option></select></label>
	</form>
	<div id="notice-status" class="notice-status" aria-live="polite"><span class="spinner"></span> Loading archive...</div>
	<div id="notice-list" class="notice-grid"></div>
	<div class="load-more-wrap"><button id="load-more" class="button button-light" type="button" hidden>Load more notices</button></div>
</section>
<script>window.NOTICE_BOARD = { endpoint: '<?= BASE_URL ?>/api/notices.php', status: '<?= h($boardStatus) ?>', detailsUrl: '<?= BASE_URL ?>/notice-details.php' };</script>
<script src="<?= BASE_URL ?>/assets/js/script.js?v=20261024-ui"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
