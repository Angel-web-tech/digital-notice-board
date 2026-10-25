<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
$token = (string) ($_GET['token'] ?? $_POST['token'] ?? '');
$tokenHash = preg_match('/^[a-f0-9]{64}$/i', $token) ? hash('sha256', $token) : str_repeat('0', 64);
$statement = db()->prepare("SELECT pr.id,pr.user_id,pr.token_hash,u.username FROM password_resets pr JOIN users u ON u.user_id=pr.user_id WHERE pr.used_at IS NULL AND pr.expires_at>NOW() AND u.status='active'");
$statement->execute();
$reset = null;
foreach ($statement->fetchAll() as $candidate) {
	if (hash_equals((string) $candidate['token_hash'], $tokenHash)) { $reset = $candidate; break; }
}
$errors=[];$success=false;
if ($_SERVER['REQUEST_METHOD']==='POST' && $reset) {
	$password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm_password']??'');
	if (!valid_staff_password($password)) $errors['password']='Use at least 8 characters, including a letter and a number.';
	if ($password!==$confirm) $errors['confirm_password']='Passwords do not match.';
	if (!verify_csrf(isset($_POST['csrf_token'])?(string)$_POST['csrf_token']:null)) $errors['form']='Your session expired. Reload and try again.';
	if (!$errors) {
		$pdo=db();$pdo->beginTransaction();
		$lock=$pdo->prepare('SELECT id FROM password_resets WHERE id=:id AND token_hash=:hash AND used_at IS NULL AND expires_at>NOW() FOR UPDATE');$lock->execute(['id'=>$reset['id'],'hash'=>$tokenHash]);
		if (!$lock->fetchColumn()) {$pdo->rollBack();$reset=null;}
		else {
			$pdo->prepare('UPDATE users SET password_hash=:hash,must_change_password=0 WHERE user_id=:id')->execute(['hash'=>password_hash($password,PASSWORD_DEFAULT),'id'=>$reset['user_id']]);
			$pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE id=:id')->execute(['id'=>$reset['id']]);
			$pdo->prepare('DELETE FROM password_resets WHERE user_id=:id AND id<>:keep')->execute(['id'=>$reset['user_id'],'keep'=>$reset['id']]);
			$pdo->prepare('DELETE FROM login_attempts WHERE username=:username')->execute(['username'=>$reset['username']]);
			log_named_activity($pdo,$reset['username'],'password_reset',(int)$reset['user_id']);
			$pdo->commit();session_regenerate_id(true);$success=true;
			header('Location: ' . BASE_URL . '/login.php'); exit;
		}
	}
}
$pageTitle='Reset password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap account-auth-wrap"><div class="auth-panel account-card recovery-card"><p class="eyebrow">Account recovery</p><?php if ($success): ?><h1>Password updated</h1><div class="success-alert" role="status" aria-live="polite">Your password has been changed.</div><a class="button button-primary account-submit" href="<?= BASE_URL ?>/login.php">Continue to sign in</a><?php elseif (!$reset): ?><h1>Link unavailable</h1><p class="auth-intro">This reset link is invalid, expired, or already used.</p><a class="button button-primary account-submit" href="<?= BASE_URL ?>/forgot-password.php">Request a new link</a><?php else: ?><h1>Choose a new password</h1><?php if (!empty($errors['form'])): ?><div class="form-alert" role="alert" aria-live="polite"><?= h($errors['form']) ?></div><?php endif; ?><form method="post" id="reset-form"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><input type="hidden" name="token" value="<?= h($token) ?>"><div class="account-field"><label class="form-field" for="reset-password">New password</label><div class="password-input-wrap"><input id="reset-password" name="password" type="password" required><button class="show-password" type="button" data-target="reset-password" aria-pressed="false" aria-label="Show password">Show</button></div><div class="strength-meter" aria-hidden="true"><span id="strength-bar"></span></div><small class="password-strength" id="password-strength" aria-live="polite">Password strength: weak</small><small class="field-error" aria-live="polite"><?= h($errors['password']??'') ?></small></div><div class="account-field"><label class="form-field" for="reset-confirm-password">Confirm password</label><div class="password-input-wrap"><input id="reset-confirm-password" name="confirm_password" type="password" required autocomplete="new-password"><button class="show-password" type="button" data-target="reset-confirm-password" aria-pressed="false" aria-label="Show password">Show</button></div><small class="field-error" aria-live="polite"><?= h($errors['confirm_password']??'') ?></small></div><button class="button button-primary account-submit" type="submit">Reset password</button></form><?php endif; ?></div></section><script src="<?= BASE_URL ?>/assets/js/admin.js?v=20261024-ui"></script>
<?php require __DIR__ . '/includes/footer.php'; ?>