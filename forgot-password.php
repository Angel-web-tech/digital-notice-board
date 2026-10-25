<?php
declare(strict_types=1);
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/mailer.php';
$message = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$email = trim((string) ($_POST['email'] ?? ''));
	$ip = mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 0, 45);
	if (!verify_csrf(isset($_POST['csrf_token']) ? (string) $_POST['csrf_token'] : null)) {
		$message = 'If that email is registered, a reset link has been sent.';
	} else {
		try {
			$pdo = db();
			$emailHash = hash('sha256', strtolower($email));
			$emailAllowed = ip_rate_limit($pdo, 'reset_email_' . substr($emailHash, 0, 16), $ip, 3, 3600, false);
			$ipAllowed = ip_rate_limit($pdo, 'reset_ip', $ip, 3, 3600);
			if ($emailAllowed && $ipAllowed && filter_var($email, FILTER_VALIDATE_EMAIL)) {
				$find = $pdo->prepare("SELECT user_id,full_name,email,status FROM users WHERE email=:email AND status='active' LIMIT 1");
				$find->execute(['email'=>$email]);
				$user = $find->fetch();
				if ($user) {
					$pdo->prepare('UPDATE password_resets SET used_at=NOW() WHERE user_id=:id AND used_at IS NULL')->execute(['id'=>$user['user_id']]);
					$token = bin2hex(random_bytes(32));
					$insert = $pdo->prepare('INSERT INTO password_resets (user_id,token_hash,expires_at,request_ip) VALUES (:id,:hash,DATE_ADD(NOW(),INTERVAL 30 MINUTE),:ip)');
					$insert->execute(['id'=>$user['user_id'],'hash'=>hash('sha256',$token),'ip'=>$ip]);
					$app = account_config();
					$resetUrl = rtrim((string) ($app['base_url'] ?? 'http://localhost' . BASE_URL), '/') . '/reset-password.php?token=' . rawurlencode($token);
					send_password_reset_email((string)$user['email'],(string)($user['full_name'] ?: $user['email']),$resetUrl);
				}
				record_ip_rate_limit($pdo, 'reset_email_' . substr($emailHash, 0, 24), $ip);
				record_ip_rate_limit($pdo, 'reset_ip', $ip);
			}
		} catch (Throwable $exception) { error_log('Password reset request failed: ' . $exception->getMessage()); }
		$message = 'If that email is registered, a reset link has been sent.';
	}
}
$pageTitle='Forgot password';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-wrap account-auth-wrap"><div class="auth-panel account-card recovery-card"><p class="eyebrow">Account recovery</p><h1>Forgot password?</h1><p class="auth-intro">Enter the email attached to your staff account.</p><?php if ($message): ?><div class="success-alert" role="status" aria-live="polite"><?= h($message) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?= h(csrf_token()) ?>"><label class="form-field">Email address<input type="email" name="email" maxlength="254" value="<?= h($email) ?>" required autocomplete="email"></label><button class="button button-primary account-submit" type="submit">Send reset link</button></form><a class="subtle-link return-link" href="<?= BASE_URL ?>/login.php">Return to sign in</a></div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>