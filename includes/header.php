<?php
require_once __DIR__ . '/auth.php';
$pageTitle = $pageTitle ?? 'Campus Notice Board';
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?= h($pageTitle) ?> | Campus Notice Board</title>
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=20261024-ui">
	<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/enhancements.css?v=20261024-ui">
	<?php if (str_starts_with($_SERVER['PHP_SELF'] ?? '', BASE_URL . '/admin/') || in_array(basename($_SERVER['PHP_SELF'] ?? ''), ['login.php', 'setup-admin.php', 'register.php', 'forgot-password.php', 'reset-password.php'], true)): ?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=20261024-ui"><?php endif; ?>
	<?php if (str_starts_with($_SERVER['PHP_SELF'] ?? '', BASE_URL . '/admin/')): ?><link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin-enhancements.css?v=20261024-ui"><?php endif; ?>
	<script>window.APP_BASE_URL = <?= json_encode(BASE_URL) ?>;window.APP_USER_ROLE = <?= json_encode((string) ($_SESSION['role'] ?? '')) ?>;window.APP_USER_ID = <?= (int) ($_SESSION['user_id'] ?? 0) ?>;window.CURRENT_USER_ID = window.APP_USER_ID;</script>
	<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
	<script src="<?= BASE_URL ?>/assets/js/date-format.js?v=20261024-ui"></script>
	<script src="<?= BASE_URL ?>/assets/js/theme.js?v=20261024-ui"></script>
</head>
<body>
<header class="site-header">
	<a class="brand" href="<?= BASE_URL ?>/"><span class="brand-mark">CN</span><span>Campus Notice Board</span></a>
	<button class="mobile-menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="site-navigation"><span></span><span></span><span></span></button>
	<nav id="site-navigation" class="site-nav" aria-label="Main navigation">
		<a href="<?= BASE_URL ?>/" <?= basename($_SERVER['PHP_SELF'] ?? '') === 'index.php' ? 'aria-current="page"' : '' ?>>Notices</a><a href="<?= BASE_URL ?>/archived-notices.php" <?= basename($_SERVER['PHP_SELF'] ?? '') === 'archived-notices.php' ? 'aria-current="page"' : '' ?>>Archive</a>
		<?php if (isset($_SESSION['user_id'])): ?><a href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a><a href="<?= BASE_URL ?>/admin/profile.php">Profile</a><a href="<?= BASE_URL ?>/admin/change-password.php">Password</a><a href="<?= BASE_URL ?>/logout.php">Log out</a><?php else: ?><a href="<?= BASE_URL ?>/login.php">Admin login</a><?php endif; ?>
		<time class="campus-clock" id="campus-clock" aria-label="Current campus time in India">--:-- IST</time>
		<button class="theme-toggle" type="button" aria-label="Switch to dark mode" title="Toggle dark mode"><span aria-hidden="true">◐</span></button>
	</nav>
</header>
<main class="site-main">
<?php if (str_starts_with($_SERVER['PHP_SELF'] ?? '', BASE_URL . '/admin/')): ?><button class="admin-menu-toggle" type="button" aria-label="Open admin navigation" aria-expanded="false" aria-controls="admin-sidebar"><span aria-hidden="true">☰</span></button><button class="admin-sidebar-overlay" type="button" aria-label="Close admin navigation" tabindex="-1"></button><?php endif; ?>
