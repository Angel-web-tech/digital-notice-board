<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_role(['admin','faculty']);
$pdo=db();$query=$pdo->prepare('SELECT user_id,full_name,email,username,role FROM users WHERE user_id=:id');$query->execute(['id'=>$_SESSION['user_id']]);$user=$query->fetch() ?: ['user_id'=>(int)$_SESSION['user_id'],'full_name'=>'','email'=>'','username'=>(string)($_SESSION['username']??''),'role'=>(string)($_SESSION['role']??'')];$errors=[];$saved=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
	$name=trim((string)($_POST['full_name']??''));$email=trim((string)($_POST['email']??''));
	if(!valid_full_name($name))$errors['full_name']='Enter 3 to 60 letters, spaces or dots.';
	if(!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen($email)>254)$errors['email']='Enter a valid email address.';
	$unique=$pdo->prepare('SELECT 1 FROM users WHERE email=:email AND user_id<>:id');$unique->execute(['email'=>$email,'id'=>$user['user_id']]);if($unique->fetchColumn())$errors['email']='That email address is already in use.';
	if(!verify_csrf(isset($_POST['csrf_token'])?(string)$_POST['csrf_token']:null))$errors['form']='Your session expired. Reload and try again.';
	if(!$errors){$update=$pdo->prepare('UPDATE users SET full_name=:name,email=:email WHERE user_id=:id');$update->execute(['name'=>$name,'email'=>$email,'id'=>$user['user_id']]);$user['full_name']=$name;$user['email']=$email;$saved=true;log_activity($pdo,'profile_update');}
}
$pageTitle='My Profile';require __DIR__.'/../includes/header.php';
?>
<section class="admin-shell"><aside class="admin-sidebar"><a href="<?= BASE_URL ?>/admin/dashboard.php">Overview</a><a href="<?= BASE_URL ?>/admin/manage-notices.php">Manage notices</a><a class="active" href="<?= BASE_URL ?>/admin/profile.php">My profile</a><a href="<?= BASE_URL ?>/admin/change-password.php">Change password</a></aside><div class="admin-content"><div class="admin-heading"><div><p class="eyebrow">Account settings</p><h1>My profile</h1></div></div><div class="admin-form profile-form account-card"><?php if($saved):?><div class="success-alert" role="status">Profile updated.</div><?php endif;?><?php if(!empty($errors['form'])):?><div class="form-alert" role="alert"><?=h($errors['form'])?></div><?php endif;?><form method="post"><input type="hidden" name="csrf_token" value="<?=h(csrf_token())?>"><label class="form-field">Username<input value="<?=h($user['username'])?>" disabled></label><label class="form-field">Role<input value="<?=h(ucfirst($user['role']))?>" disabled></label><label class="form-field">Full name<input name="full_name" maxlength="60" value="<?=h($user['full_name']??'')?>" required><small class="field-error"><?=h($errors['full_name']??'')?></small></label><label class="form-field">Email<input type="email" name="email" maxlength="254" value="<?=h($user['email']??'')?>" required><small class="field-error"><?=h($errors['email']??'')?></small></label><button class="button button-primary" type="submit">Save profile</button></form></div></div></section>
<?php require __DIR__.'/../includes/footer.php'; ?>