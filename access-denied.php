<?php
require_once __DIR__ . '/includes/helpers.php';
$pageTitle = 'Access denied';
require __DIR__ . '/includes/header.php';
?>
<section class="not-found-page"><p class="eyebrow">Access control</p><h1>Access denied</h1><p>Your account does not have permission to open this area.</p><a class="button button-primary" href="<?= BASE_URL ?>/">Return to the notice board</a></section>
<?php require __DIR__ . '/includes/footer.php'; ?>