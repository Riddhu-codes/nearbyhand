<?php
require __DIR__.'/includes/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $selectedRole = $_POST['role'] ?? 'customer';
    $roleTables = [
        'customer' => 'customers',
        'worker' => 'workers',
        'admin' => 'admins'
    ];
    $ok = false;
    $role = in_array($selectedRole, ['customer', 'worker', 'admin'], true) ? $selectedRole : 'customer';
    $table = $roleTables[$role];
    $email = strtolower(trim($email));
    $stmt = $pdo->prepare("SELECT * FROM $table WHERE LOWER(email)=? LIMIT 1");
    $stmt->execute([$email]);
    $u = $stmt->fetch();
    if ($u && password_verify($password, $u['password_hash'])) { $ok=true; }
    if (!$u) {
        foreach ($roleTables as $otherRole => $otherTable) {
            if ($otherRole === $role) { continue; }
            $stmt = $pdo->prepare("SELECT id FROM $otherTable WHERE LOWER(email)=? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                flash('This email is registered as a '.ucfirst($otherRole).'. Please select the correct login option.', 'error');
                redirect('login.php?role='.$role);
            }
        }
    }
    if ($ok) {
        if (isset($u['status']) && $u['status'] === 'suspended') { flash('This account has been suspended.', 'error'); redirect('login.php'); }
        if ($role === 'worker' && $u['status'] !== 'approved') { flash('Your professional account is awaiting approval.', 'error'); redirect('login.php'); }
        $_SESSION['user']=['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$role];
        flash('Welcome back!');
        redirect($role==='admin'?'admin/index.php':($role==='worker'?'worker-dashboard.php':'index.php'));
    }
    flash('Invalid email or password.', 'error');
}
$pageTitle='Login';
$loginRole = ($_SERVER['REQUEST_METHOD'] === 'POST' ? ($_POST['role'] ?? 'customer') : ($_GET['role'] ?? 'customer'));
if (!in_array($loginRole, ['customer', 'worker', 'admin'], true)) { $loginRole = 'customer'; }
require __DIR__.'/includes/header.php';
?>
<div class="auth-wrap"><form method="post" class="auth-card"><h1>Welcome back</h1><p class="login-subtitle">Choose how you want to continue</p><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><?php if ($loginRole === 'admin'): ?><div class="admin-login-note"><strong>Main Admin</strong><p>Use your secured admin email and password.</p></div><input type="hidden" name="role" value="admin"><?php else: ?><fieldset class="login-role-picker"><legend>Login as</legend><label class="login-role"><input type="radio" name="role" value="customer" <?= $loginRole === 'customer' ? 'checked' : '' ?>><span><i class="bi bi-person-fill"></i><strong>Customer</strong><small>Book and manage services</small></span></label><label class="login-role"><input type="radio" name="role" value="worker" <?= $loginRole === 'worker' ? 'checked' : '' ?>><span><i class="bi bi-tools"></i><strong>Worker</strong><small>Manage jobs and availability</small></span></label></fieldset><?php endif ?><label class="field"><span>Email address</span><input type="email" name="email" required></label><label class="field"><span>Password</span><input type="password" name="password" required></label><button class="btn btn-primary">Login securely</button><p class="auth-alt">New to NearbyHand? <a href="register.php">Create a customer account</a> or <a href="worker-register.php">register as a worker</a></p></form></div>
<?php require __DIR__.'/includes/footer.php'; ?>
