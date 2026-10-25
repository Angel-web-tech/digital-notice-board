<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';

function send_password_reset_email(string $recipient, string $name, string $resetUrl): bool
{
	$configPath = __DIR__ . '/../config/mail.php';
	if (is_file($configPath)) {
		$config = require $configPath;
		$files = [__DIR__ . '/phpmailer/Exception.php', __DIR__ . '/phpmailer/PHPMailer.php', __DIR__ . '/phpmailer/SMTP.php'];
		if (array_reduce($files, static fn(bool $ok, string $file): bool => $ok && is_file($file), true)) {
			require_once $files[0];
			require_once $files[1];
			require_once $files[2];
			try {
				$mail = new PHPMailer\PHPMailer\PHPMailer(true);
				$mail->isSMTP();
				$mail->Host = (string) $config['host'];
				$mail->Port = (int) $config['port'];
				$mail->SMTPAuth = true;
				$mail->Username = (string) $config['username'];
				$mail->Password = (string) $config['password'];
				$mail->SMTPSecure = (string) ($config['secure'] ?? 'tls');
				$mail->CharSet = 'UTF-8';
				$mail->setFrom((string) $config['from_address'], (string) $config['from_name']);
				$mail->addAddress($recipient, $name);
				$mail->isHTML(true);
				$mail->Subject = 'Reset your Campus Notice Board password';
				$safeName = h($name);
				$safeUrl = h($resetUrl);
				$mail->Body = '<div style="font-family:Arial,sans-serif;background:#F5F1E8;padding:32px;color:#26332B"><div style="max-width:560px;margin:auto;background:#FFFFFF;border-top:5px solid #234735;padding:28px"><p style="color:#234735;font-weight:bold">Campus Notice Board</p><h1 style="font-family:Georgia,serif;font-weight:normal">Password reset</h1><p>Hello ' . $safeName . ',</p><p>Use the secure link below to choose a new password. This link expires in 30 minutes.</p><p><a style="display:inline-block;padding:12px 18px;background:#234735;color:#FFFFFF;text-decoration:none" href="' . $safeUrl . '">Reset password</a></p><p>If you did not request this, you can ignore this email.</p></div></div>';
				$mail->AltBody = "Hello {$name},\n\nReset your password using this link within 30 minutes:\n{$resetUrl}\n\nIf you did not request this, ignore this email.";
				return $mail->send();
			} catch (Throwable $exception) {
				error_log('Password reset email failed: ' . $exception->getMessage());
			}
		}
	}
	$directory = __DIR__ . '/../logs';
	if (!is_dir($directory)) @mkdir($directory, 0750, true);
	$line = date('Y-m-d H:i:s') . ' password-reset-fallback ' . preg_replace('/[\r\n]/', '', $resetUrl) . PHP_EOL;
	return file_put_contents($directory . '/mail.log', $line, FILE_APPEND | LOCK_EX) !== false;
}