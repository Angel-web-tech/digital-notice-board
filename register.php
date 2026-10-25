<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
if (isset($_SESSION['user_id'])) { header('Location: ' . BASE_URL . '/admin/dashboard.php'); exit; }
$errors = [];
$created = false;
$form = ['full_name' => '', 'email' => '', 'username' => ''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$form['full_name'] = trim((string) ($_POST['full_name'] ?? ''));
	$form['email'] = trim((string) ($_POST['email'] ?? ''));
	$form['username'] = trim((string) ($_POST['username'] ?? ''));
	$password = (string) ($_POST['password'] ?? '');
	$confirm = (string) ($_POST['confirm_password'] ?? '');
	$code = (string) ($_POST['registration_code'] ?? '');
	$ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
	$app = account_config();
	if (trim((string) ($_POST['website'] ?? '')) !== '') $errors['form'] = 'Registration could not be completed.';
	if (!valid_full_name($form['full_name'])) $errors['full_name'] = 'Enter 3 to 60 letters, spaces or dots.';
	if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || mb_strlen($form['email']) > 254) $errors['email'] = 'Enter a valid email address.';
	if (!preg_match('/^[A-Za-z0-9_]{3,30}$/', $form['username'])) $errors['username'] = 'Use 3 to 30 letters, numbers or underscores.';
	if (!valid_staff_password($password)) $errors['password'] = 'Use at least 8 characters, including a letter and a number.';
	if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match.';
	if (($app['staff_registration_code'] ?? '') !== '' && !hash_equals((string) $app['staff_registration_code'], $code)) $errors['registration_code'] = 'Registration code is incorrect.';
	if (!verify_csrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) $errors['form'] = 'Your session expired. Reload and try again.';
	try {
		$pdo = db();
		if (!ip_rate_limit($pdo, 'register', $ip, 5, 3600)) $errors['form'] = 'Too many registration attempts. Try again later.';
		$emailCheck = $pdo->prepare('SELECT 1 FROM users WHERE email=:email'); $emailCheck->execute(['email' => $form['email']]);
		if ($emailCheck->fetchColumn()) $errors['email'] = 'That email address is already registered.';
		$usernameCheck = $pdo->prepare('SELECT 1 FROM users WHERE username=:username'); $usernameCheck->execute(['username' => $form['username']]);
		if ($usernameCheck->fetchColumn()) $errors['username'] = 'That username is already in use.';
		if (!$errors) {
			record_ip_rate_limit($pdo, 'register', $ip);
			$insert = $pdo->prepare("INSERT INTO users (full_name,email,username,password_hash,role,status,must_change_password) VALUES (:name,:email,:username,:hash,'faculty','pending',0)");
			$insert->execute(['name'=>$form['full_name'],'email'=>$form['email'],'username'=>$form['username'],'hash'=>password_hash($password,PASSWORD_DEFAULT)]);
			log_named_activity($pdo, $form['username'], 'register', (int) $pdo->lastInsertId());
			$created = true;
		}
	} catch (Throwable $exception) { error_log('Staff registration failed: ' . $exception->getMessage()); $errors['form'] = 'Registration is temporarily unavailable.'; }
}
$pageTitle = 'Create Staff Account';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap account-auth-wrap"><div class="auth-panel account-card register-card"><p class="eyebrow">Staff access</p><?php if ($created): ?><h1>Request received</h1><div class="success-alert" role="status" aria-live="polite">Account created, waiting for admin approval.</div><a class="button button-primary account-submit" href="<?= BASE_URL ?>/login.php">Return to sign in</a><?php else: ?><h1>Create staff account</h1><p class="auth-intro">Your account will be available after an administrator approves it.</p><?php if (!empty($errors['form'])): ?><div class="form-alert" role="alert" aria-live="polite"><?= h($errors['form']) ?></div><?php endif; ?><form id="register-form" method="post" novalidate><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><div class="honeypot-trap" aria-hidden="true"><label for="website" class="honeypot-label">Website</label><input type="text" id="website" name="website" tabindex="-1" aria-hidden="true" autocomplete="off"></div><div class="register-grid"><label class="form-field">Full name<input name="full_name" maxlength="60" value="<?= h($form['full_name']) ?>" required><small class="field-error" aria-live="polite"><?= h($errors['full_name'] ?? '') ?></small></label><label class="form-field">Email<input name="email" type="email" maxlength="254" value="<?= h($form['email']) ?>" required><small class="field-error" aria-live="polite"><?= h($errors['email'] ?? '') ?></small></label><label class="form-field">Username<input name="username" minlength="3" maxlength="30" value="<?= h($form['username']) ?>" required><small class="field-error" aria-live="polite"><?= h($errors['username'] ?? '') ?></small></label><div class="account-field"><label class="form-field" for="register-password">Password</label><div class="password-input-wrap"><input id="register-password" name="password" type="password" required><button class="show-password" type="button" data-target="register-password" aria-pressed="false" aria-label="Show password">Show</button></div><div class="strength-meter" aria-hidden="true"><span id="strength-bar"></span></div><small class="password-strength" id="password-strength" aria-live="polite">Password strength: weak</small><small class="field-error" aria-live="polite"><?= h($errors['password'] ?? '') ?></small></div><div class="account-field register-full"><label class="form-field" for="register-confirm-password">Confirm password</label><div class="password-input-wrap"><input id="register-confirm-password" name="confirm_password" type="password" required autocomplete="new-password"><button class="show-password" type="button" data-target="register-confirm-password" aria-pressed="false" aria-label="Show password">Show</button></div><small class="field-error" aria-live="polite"><?= h($errors['confirm_password'] ?? '') ?></small></div><?php if (($app['staff_registration_code'] ?? '') !== ''): ?><label class="form-field register-full">Staff registration code<input name="registration_code" required><small class="field-error" aria-live="polite"><?= h($errors['registration_code'] ?? '') ?></small></label><?php endif; ?></div><button class="button button-primary account-submit" type="submit">Create account</button></form><div class="auth-options"><span>Already have an account?</span> <a href="<?= BASE_URL ?>/login.php">Sign in</a></div><?php endif; ?></div></section>
<script src="<?= BASE_URL ?>/assets/js/admin.js?v=20261024-ui"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>