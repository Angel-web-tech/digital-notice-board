<?php
require_once __DIR__ . '/includes/helpers.php';
http_response_code(404);
$pageTitle = 'Page not found';
require __DIR__ . '/includes/header.php';
?>
<section class="not-found-page"><svg class="empty-illustration" viewBox="0 0 120 90" role="img" aria-label="An empty campus notice board"><path d="M16 15h88v56H16zM30 28h60M30 39h42M30 50h50M53 71v12m-18 0h52" fill="none" stroke="currentColor" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><circle cx="96" cy="65" r="13" fill="#F5F1E8" stroke="currentColor" stroke-width="4"/><path d="m106 75 9 9" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg><p class="eyebrow">404 · Wrong corridor</p><h1>This page isn't on the board.</h1><p>The address may have changed, or this notice is no longer available.</p><a class="button button-primary" href="<?= BASE_URL ?>/">Back to the notice board</a></section>
<?php require __DIR__ . '/includes/footer.php'; ?>