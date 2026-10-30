<?php
require __DIR__.'/includes/bootstrap.php';
if ($user = current_user()) {
    if ($user['role'] === 'worker') redirect('worker-dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $rawMobile = preg_replace('/[^0-9]/', '', (string)($_POST['mobile'] ?? ''));
    $mobile = $rawMobile;
    if (strlen($mobile) === 12 && substr($mobile, 0, 2) === '91') $mobile = substr($mobile, 2);
    elseif (strlen($mobile) === 11 && substr($mobile, 0, 1) === '0') $mobile = substr($mobile, 1);
    $stmt = $pdo->query("SELECT * FROM workers WHERE mobile IS NOT NULL AND mobile<>''");
    $worker = null;
    foreach ($stmt->fetchAll() as $candidate) {
        $stored = preg_replace('/[^0-9]/', '', (string)$candidate['mobile']);
        if (strlen($stored) === 12 && substr($stored, 0, 2) === '91') $stored = substr($stored, 2);
        elseif (strlen($stored) === 11 && substr($stored, 0, 1) === '0') $stored = substr($stored, 1);
        if ($stored === $mobile) { $worker = $candidate; break; }
    }

    if (($worker && password_verify((string)($_POST['password'] ?? ''), $worker['password_hash'])) || (isset($_POST['otp']) && isset($_SESSION['worker_otp']) &&     password_verify((string)$_POST['otp'], (string)$_SESSION['worker_otp']) && ($_SESSION['worker_otp_expires'] ?? 0) >= time() && ($_SESSION['worker_otp_mobile'] ?? '') === $mobile)) {
        if (!$worker) { flash('No account found for this mobile number.', 'error'); }
        elseif ($worker['status'] === 'suspended') { flash('This worker account has been suspended.', 'error'); }
        elseif ($worker['status'] !== 'approved') { flash('Your application is awaiting admin approval.', 'error'); }
        else {
            unset($_SESSION['worker_otp'], $_SESSION['worker_otp_mobile'], $_SESSION['worker_otp_expires']);
            $_SESSION['user'] = ['id'=>$worker['id'],'name'=>$worker['name'],'email'=>$worker['email'],'role'=>'worker'];
            redirect('worker-dashboard.php');
        }
    } elseif (($worker && ($_POST['password'] ?? '') === '') && isset($_POST['send_otp'])) {
        $otp = (string)random_int(100000, 999999);
        $_SESSION['worker_otp'] = password_hash($otp, PASSWORD_DEFAULT);
        $_SESSION['worker_otp_mobile'] = $mobile;
        $_SESSION['worker_otp_expires'] = time() + 300;
        // SMS provider not configured: development fallback. Configure SMS_API_KEY before production.
        flash('OTP generated for local testing. Development OTP: '.$otp, 'success');
    } else {
        flash('Invalid mobile number, password, or OTP.', 'error');
    }
}
$pageTitle='Worker login';
require __DIR__.'/includes/header.php';
?>
<div class="auth-wrap"><form method="post" class="auth-card worker-auth"><div class="role-icon"><i class="bi bi-tools"></i></div><h1>Worker login</h1><p class="login-subtitle">Use your mobile number — no Gmail required.</p><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><label class="field"><span>Mobile number</span><input type="tel" name="mobile" value="<?=e($_POST['mobile'] ?? '')?>" placeholder="10 digit mobile number" minlength="10" required></label><label class="field"><span>Password (alternative)</span><input type="password" name="password" placeholder="Use your account password"></label><button class="btn btn-primary" name="send_otp" value="1">Send / create OTP</button><p class="auth-alt">Enter the OTP below if it has been sent:</p><label class="field"><span>6-digit OTP</span><input inputmode="numeric" name="otp" pattern="[0-9]{6}" maxlength="6"></label><button class="btn btn-outline">Verify OTP and login</button><p class="auth-alt">New worker? <a href="worker-register.php">Create your profile</a></p><p class="integration-note"><strong>SMS setup:</strong> A real SMS provider and API credentials are required before deployment.</p></form></div>
<?php require __DIR__.'/includes/footer.php'; ?>