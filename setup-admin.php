<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$pageMessage = '';
$errors = [];
$setupOpen = false;
try { $setupOpen = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn() === 0; }
catch (Throwable $exception) { error_log('Admin setup check failed: ' . $exception->getMessage()); $pageMessage = 'Database is not ready. Import the SQL file and check config/database.php.'; }
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $setupOpen) {
	$username = trim((string) ($_POST['username'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$confirm = (string) ($_POST['confirm_password'] ?? '');
	if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $username)) $errors['username'] = 'Use 3 to 30 letters, numbers or underscores.';
	if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
	if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
	if (!verify_csrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) $errors['form'] = 'Your session expired. Reload the page.';
	if (!$errors) {
		try {
			$statement = db()->prepare('INSERT INTO users (username, password_hash, role) VALUES (:username, :password_hash, \'admin\')');
			$statement->execute(['username' => $username, 'password_hash' => password_hash($password, PASSWORD_DEFAULT)]);
			$pageMessage = 'First administrator created. You can now sign in.'; $setupOpen = false;
		} catch (Throwable $exception) { error_log('Admin creation failed: ' . $exception->getMessage()); $errors['form'] = 'Administrator could not be created.'; }
	}
}
$pageTitle = 'Create First Admin';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap"><div class="auth-panel"><p class="eyebrow">One-time setup</p><h1>Create first admin</h1><?php if ($pageMessage): ?><div class="form-alert <?= $setupOpen ? '' : 'success-alert' ?>"><?= h($pageMessage) ?></div><?php endif; ?><?php if ($setupOpen): ?><?php if (!empty($errors['form'])): ?><div class="form-alert"><?= h($errors['form']) ?></div><?php endif; ?><form method="post" novalidate><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><label class="form-field">Username<input name="username" required minlength="3" maxlength="30" pattern="[A-Za-z0-9_]{3,30}" autocomplete="username"><small class="field-error"><?= h($errors['username'] ?? '') ?></small></label><label class="form-field">Password<input name="password" type="password" required minlength="8" autocomplete="new-password"><small class="field-error"><?= h($errors['password'] ?? '') ?></small></label><label class="form-field">Confirm password<input name="confirm_password" type="password" required minlength="8" autocomplete="new-password"><small class="field-error"><?= h($errors['confirm_password'] ?? '') ?></small></label><button class="button button-primary" type="submit">Create administrator</button></form><?php else: ?><p>Setup is closed because an administrator already exists.</p><a class="button button-primary" href="<?= BASE_URL ?>/login.php">Go to sign in</a><?php endif; ?></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
