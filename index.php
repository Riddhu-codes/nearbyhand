<?php
require __DIR__.'/includes/bootstrap.php';
$pageTitle='Choose your role';
require __DIR__.'/includes/header.php';
?>
<section class="role-entry"><div class="container"><div class="role-heading"><div class="eyebrow">Welcome to NearbyHand</div><h1>What would you like to do?</h1><p>Choose an option, or explore customer services without logging in.</p></div><div class="role-grid"><a class="role-card customer-role" href="customer-home.php"><div class="role-icon"><i class="bi bi-house-heart-fill"></i></div><span>Book a Service</span><h2>Customer</h2><p>Find trusted plumbers and electricians near you.</p><strong>Explore services <i class="bi bi-arrow-right"></i></strong></a><a class="role-card worker-role" href="worker-login.php"><div class="role-icon"><i class="bi bi-tools"></i></div><span>Join NearbyHand</span><h2>Worker</h2><p>Get local jobs and manage your work from your mobile.</p><strong>Worker login / registration <i class="bi bi-arrow-right"></i></strong></a></div><a class="admin-entry-link" href="login.php?role=admin">Main Admin login</a></div></section>
<?php require __DIR__.'/includes/footer.php'; ?>
