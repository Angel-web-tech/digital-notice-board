<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
if (isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . '/admin/dashboard.php'); exit; }
$errors = [];
$username = '';
$lockRemaining = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim((string) ($_POST['identity'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
	if (strlen($password) < 8) $errors['password'] = 'Password must be at least 8 characters.';
	if (!verify_csrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) $errors['form'] = 'Your session expired. Reload the page and try again.';
	if (!$errors) {
		try {
			$pdo = db();
			// Lockout counts follow the username even if a failed attempt comes from another IP.
			$attempts = $pdo->prepare('SELECT COUNT(*) AS failures, MAX(attempted_at) AS latest FROM login_attempts WHERE username=:username AND was_successful=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
			$attempts->execute(['username' => $username]);
			$lock = $attempts->fetch();
			if ((int) $lock['failures'] >= 5) {
				$lockRemaining = max(1, 300 - (time() - strtotime((string) $lock['latest'])));
				$errors['form'] = 'Too many failed attempts. Try again in ' . (int) ceil($lockRemaining / 60) . ' minute(s).';
			} else {
			$statement = $pdo->prepare('SELECT user_id, username, password_hash, role, status, must_change_password FROM users WHERE username = :identity OR email = :email_identity LIMIT 1');
			$statement->execute(['identity' => $username, 'email_identity' => $username]);
			$user = $statement->fetch();
			$passwordMatches = $user && password_verify($password, $user['password_hash']);
			$valid = $passwordMatches && ($user['status'] ?? 'active') === 'active' && in_array($user['role'], ['admin', 'faculty'], true);
			$record = $pdo->prepare('INSERT INTO login_attempts (username, ip_address, attempted_at, was_successful) VALUES (:username, :ip, NOW(), :success)');
			$record->execute(['username' => $user['username'] ?? mb_substr($username, 0, 30), 'ip' => $ip, 'success' => $passwordMatches ? 1 : 0]);
			if ($valid) {
				$clearAttempts = $pdo->prepare('DELETE FROM login_attempts WHERE username=:username');
				$clearAttempts->execute(['username' => $user['username']]);
				session_regenerate_id(true);
				$_SESSION['user_id'] = (int) $user['user_id'];
				$_SESSION['username'] = $user['username'];
				$_SESSION['role'] = $user['role'];
				$_SESSION['must_change_password'] = (int) ($user['must_change_password'] ?? 0) === 1;
				$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
				log_named_activity($pdo, $user['username'], 'login', (int) $user['user_id']);
				if ((int) ($user['must_change_password'] ?? 0) === 1) { header('Location: ' . BASE_URL . '/admin/change-password.php?required=1'); exit; }
				header('Location: ' . BASE_URL . '/admin/dashboard.php'); exit;
			}
			if ($passwordMatches && $user && ($user['status'] ?? 'active') !== 'active') {
				$errors['form'] = $user['status'] === 'pending' ? 'Your account is waiting for admin approval.' : 'This account is not available. Contact an administrator.';
			} else {
				log_named_activity($pdo, $user['username'] ?? mb_substr($username, 0, 30), 'failed_login', $user ? (int) $user['user_id'] : null);
				$recentFailures = $pdo->prepare('SELECT COUNT(*) FROM login_attempts WHERE username=:username AND was_successful=0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)');
				$recentFailures->execute(['username' => $user['username'] ?? mb_substr($username, 0, 30)]);
				if ((int) $recentFailures->fetchColumn() >= 5) $errors['form'] = 'Too many failed attempts. Try again in 5 minutes.';
				else $errors['form'] = 'Username or password is incorrect.';
			}
			}
		} catch (Throwable $exception) { error_log('Login failed: ' . $exception->getMessage()); $errors['form'] = 'Sign-in is temporarily unavailable.'; }
	}
}
$pageTitle = 'Admin Login';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap account-auth-wrap"><div class="auth-panel account-card login-card"><p class="eyebrow">Staff access</p><h1>Welcome back</h1><p class="auth-intro">Sign in to continue to the campus workspace.</p><?php if (!empty($_SESSION['login_success'])): ?><div class="success-alert" role="status" aria-live="polite"><?= h($_SESSION['login_success']) ?></div><?php unset($_SESSION['login_success']); endif; ?><?php if (!empty($errors['form'])): ?><div class="form-alert" role="alert" aria-live="polite"><?= h($errors['form']) ?></div><?php endif; ?><form id="login-form" method="post" novalidate><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><label class="form-field">Username or email<input name="identity" required maxlength="254" value="<?= h($username) ?>" autocomplete="username"><small class="field-error" aria-live="polite"></small></label><div class="account-field"><label class="form-field" for="login-password">Password</label><div class="password-input-wrap"><input id="login-password" name="password" type="password" required minlength="8" autocomplete="current-password"><button class="show-password" type="button" data-target="login-password" aria-pressed="false" aria-label="Show password">Show</button></div><small class="field-error" aria-live="polite"><?= h($errors['password'] ?? '') ?></small><a class="forgot-link" href="<?= BASE_URL ?>/forgot-password.php">Forgot password?</a></div><button class="button button-primary account-submit" type="submit">Sign in</button></form><div class="auth-options"><span>New staff member?</span> <a href="<?= BASE_URL ?>/register.php">Create staff account</a></div><a class="subtle-link return-link" href="<?= BASE_URL ?>/">Return to noticeboard</a></div></section>
<script>
document.getElementById('login-form').addEventListener('submit', function (event) {
	const username = this.elements.identity;
	const password = this.elements.password;
	const usernameError = username.parentElement.querySelector('.field-error');
	const passwordError = (password.closest('.account-field, .form-field') || password.parentElement).querySelector('.field-error');
	if (usernameError) usernameError.textContent = username.value.trim().length ? '' : 'Enter your username or email.';
	if (passwordError) passwordError.textContent = password.value.length >= 8 ? '' : 'Password must be at least 8 characters.';
	if ((usernameError && usernameError.textContent) || (passwordError && passwordError.textContent)) event.preventDefault();
});
document.querySelectorAll('.show-password').forEach(function(button){button.addEventListener('click',function(){const input=document.getElementById(this.dataset.target);if(!input)return;input.type=input.type==='password'?'text':'password';const shown=input.type==='text';this.textContent=shown?'Hide':'Show';this.setAttribute('aria-pressed',String(shown));this.setAttribute('aria-label',(shown?'Hide':'Show')+' password');});});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
