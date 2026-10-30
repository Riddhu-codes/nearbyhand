<?php
require __DIR__.'/includes/bootstrap.php';
require_role('worker');
$id = current_user()['id'];
$tab = $_GET['tab'] ?? 'jobs';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    if (isset($_POST['booking_otp'], $_POST['booking_id'])) {
        $bookingStmt=$pdo->prepare("SELECT completion_otp_hash FROM bookings WHERE id=? AND worker_id=?");
        $bookingStmt->execute([intval($_POST['booking_id']),$id]);
        $bookingOtp=$bookingStmt->fetch();
        if ($bookingOtp && $bookingOtp['completion_otp_hash'] && password_verify((string)$_POST['booking_otp'], $bookingOtp['completion_otp_hash'])) {
            $pdo->prepare("UPDATE bookings SET status='Completed' WHERE id=? AND worker_id=?")->execute([intval($_POST['booking_id']),$id]);
            $pdo->prepare("INSERT INTO booking_status_history(booking_id,status) VALUES(?,'Completed')")->execute([intval($_POST['booking_id'])]);
            flash('OTP verified. Booking completed.');
        } else { flash('Incorrect completion OTP.', 'error'); }
    } elseif (isset($_POST['booking_status'])) {
        $allowed=['Accepted','On the Way','Work Started','Work In Progress','Rejected'];
        if (in_array($_POST['booking_status'], $allowed, true)) {
            $s=$pdo->prepare("UPDATE bookings SET status=? WHERE id=? AND worker_id=?");
            $s->execute([$_POST['booking_status'],intval($_POST['booking_id']),$id]);
            $pdo->prepare("INSERT INTO booking_status_history(booking_id,status) VALUES(?,?)")
                ->execute([intval($_POST['booking_id']),$_POST['booking_status']]);
            flash('Booking status updated.');
        }
    } elseif (isset($_POST['availability'])) {
        $date=$_POST['available_date'];
        $start=$_POST['start_time'];
        $end=$_POST['end_time'];
        if ($date && $start && $end && $end > $start) {
            $pdo->prepare("INSERT INTO worker_availability(worker_id,available_date,start_time,end_time,available) VALUES(?,?,?,?,1) ON DUPLICATE KEY UPDATE end_time=VALUES(end_time), available=1")
                ->execute([$id,$date,$start,$end]);
            flash('Availability saved.');
        } else { flash('Enter a valid availability time.', 'error'); }
    } elseif (isset($_POST['delete_availability'])) {
        $pdo->prepare("DELETE FROM worker_availability WHERE id=? AND worker_id=?")
            ->execute([intval($_POST['availability_id']),$id]);
        flash('Availability removed.');
    } elseif (isset($_POST['profile'])) {
        $stmt=$pdo->prepare("UPDATE workers SET name=?,mobile=?,email=?,category=?,skills=?,experience_years=?,address=?,service_area=?,working_hours=? WHERE id=?");
        $stmt->execute([trim($_POST['name']),preg_replace('/[^0-9]/','',$_POST['mobile']),strtolower(trim($_POST['email'])),$_POST['category'],trim($_POST['skills']),intval($_POST['experience']),trim($_POST['address']),trim($_POST['area']),trim($_POST['hours']),$id]);
        $_SESSION['user']['name']=trim($_POST['name']);
        $_SESSION['user']['email']=strtolower(trim($_POST['email']));
        flash('Profile updated.');
    }
    redirect('worker-dashboard.php?tab='.urlencode($tab));
}

$worker=$pdo->prepare("SELECT * FROM workers WHERE id=?"); $worker->execute([$id]); $worker=$worker->fetch();
$rows=$pdo->prepare("SELECT b.*,c.name customer_name,c.mobile customer_mobile,s.name service,ca.house,ca.area,ca.city,ca.state,ca.postal_code FROM bookings b JOIN customers c ON c.id=b.customer_id JOIN services s ON s.id=b.service_id LEFT JOIN customer_addresses ca ON ca.id=b.address_id WHERE b.worker_id=? ORDER BY booking_date DESC,booking_time DESC");
$rows->execute([$id]); $rows=$rows->fetchAll();
$availability=$pdo->prepare("SELECT * FROM worker_availability WHERE worker_id=? AND available_date >= CURDATE() ORDER BY available_date,start_time"); $availability->execute([$id]); $availability=$availability->fetchAll();
$earnings=$pdo->prepare("SELECT COUNT(*) jobs, COALESCE(SUM(total_amount),0) earned FROM bookings WHERE worker_id=? AND status='Completed'"); $earnings->execute([$id]); $earnings=$earnings->fetch();
$pageTitle='Professional dashboard'; require __DIR__.'/includes/header.php';
?>
<div class="dashboard">
<aside class="side-nav">
<a class="<?= $tab==='jobs'?'active':'' ?>" href="worker-dashboard.php?tab=jobs">Jobs</a>
<a class="<?= $tab==='availability'?'active':'' ?>" href="worker-dashboard.php?tab=availability">Availability</a>
<a class="<?= $tab==='earnings'?'active':'' ?>" href="worker-dashboard.php?tab=earnings">Earnings</a>
<a class="<?= $tab==='profile'?'active':'' ?>" href="worker-dashboard.php?tab=profile">Profile</a>
</aside>
<main class="dashboard-main"><h2>Professional dashboard</h2>
<div class="stat-grid"><div class="stat">Jobs<strong><?=count($rows)?></strong></div><div class="stat">Account<span class="badge"><?=e(ucfirst($worker['status']))?></span></div><div class="stat">Area<strong><?=e($worker['service_area'])?></strong></div><div class="stat">Experience<strong><?=e($worker['experience_years'])?> yrs</strong></div></div>
<?php if($tab==='jobs'): ?>
<h3 style="margin-top:30px">Assigned jobs</h3><table><thead><tr><th>Customer</th><th>Service</th><th>Date</th><th>Address & contact</th><th>Status</th><th>Action</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><?=e($r['customer_name'])?></td><td><?=e($r['service'])?></td><td><?=e($r['booking_date'])?> <?=substr(e($r['booking_time']),0,5)?></td><td><?=e(trim($r['house'].' '.$r['area'].' '.$r['city'].' '.$r['postal_code']))?><br><a href="tel:+91<?=e(preg_replace('/[^0-9]/','',$r['customer_mobile']))?>">Call customer</a></td><td><?=e($r['status'])?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="booking_id" value="<?=e($r['id'])?>"><select name="booking_status" data-submit-on-change><option>Accepted</option><option>On the Way</option><option>Work Started</option><option>Work In Progress</option><option>Rejected</option></select><button class="btn btn-primary" type="submit">Update</button></form><form method="post" class="otp-complete"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="booking_id" value="<?=e($r['id'])?>"><input name="booking_otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="Customer completion OTP" required><button class="btn btn-outline">Complete</button></form></td></tr><?php endforeach;if(!$rows):?><tr><td colspan="6">No assigned jobs yet.</td></tr><?php endif?></tbody></table>
<?php elseif($tab==='availability'): ?>
<h3 style="margin-top:30px">Your availability</h3><div class="card"><form method="post" class="form-grid"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="availability" value="1"><label class="field"><span>Date</span><input type="date" name="available_date" required min="<?=date('Y-m-d')?>"></label><label class="field"><span>Start time</span><input type="time" name="start_time" required></label><label class="field"><span>End time</span><input type="time" name="end_time" required></label><button class="btn btn-primary">Save availability</button></form></div><table style="margin-top:20px"><thead><tr><th>Date</th><th>Time</th><th>Action</th></tr></thead><tbody><?php foreach($availability as $slot):?><tr><td><?=e($slot['available_date'])?></td><td><?=e(substr($slot['start_time'],0,5))?> - <?=e(substr($slot['end_time'],0,5))?></td><td><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="delete_availability" value="1"><input type="hidden" name="availability_id" value="<?=e($slot['id'])?>"><button class="btn btn-outline">Remove</button></form></td></tr><?php endforeach;if(!$availability):?><tr><td colspan="3">No upcoming availability added.</td></tr><?php endif?></tbody></table>
<?php elseif($tab==='earnings'): ?>
<h3 style="margin-top:30px">Earnings</h3><div class="stat-grid"><div class="stat">Completed jobs<strong><?=e($earnings['jobs'])?></strong></div><div class="stat">Total earnings<strong>₹<?=number_format((float)$earnings['earned'],2)?></strong></div></div>
<?php elseif($tab==='profile'): ?>
<h3 style="margin-top:30px">Your profile</h3><div class="card"><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf_token())?>"><input type="hidden" name="profile" value="1"><div class="form-grid"><label class="field"><span>Full name</span><input name="name" value="<?=e($worker['name'])?>" required></label><label class="field"><span>Mobile</span><input name="mobile" value="<?=e($worker['mobile'])?>" required></label><label class="field"><span>Email</span><input type="email" name="email" value="<?=e($worker['email'])?>" required></label><label class="field"><span>Category</span><select name="category"><option value="plumbing" <?= $worker['category']==='plumbing'?'selected':'' ?>>Plumbing</option><option value="electrician" <?= $worker['category']==='electrician'?'selected':'' ?>>Electrician</option></select></label><label class="field"><span>Experience</span><input type="number" name="experience" min="0" value="<?=e($worker['experience_years'])?>"></label><label class="field"><span>Service area</span><input name="area" value="<?=e($worker['service_area'])?>"></label><label class="field"><span>Working hours</span><input name="hours" value="<?=e($worker['working_hours'])?>"></label></div><label class="field"><span>Skills</span><textarea name="skills"><?=e($worker['skills'])?></textarea></label><label class="field"><span>Address</span><textarea name="address"><?=e($worker['address'])?></textarea></label><button class="btn btn-primary">Save profile</button></form></div>
<?php endif ?>
</main></div>
<?php require __DIR__.'/includes/footer.php'; ?>