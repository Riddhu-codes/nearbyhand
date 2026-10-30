<?php
require __DIR__.'/../config/database.php';
$password = getenv('ADMIN_PASSWORD');
if(!$password){fwrite(STDERR,"Set ADMIN_PASSWORD in your environment, then run php database/seed.php\n");exit(1);}
if(strlen($password)<12){fwrite(STDERR,"ADMIN_PASSWORD must contain at least 12 characters.\n");exit(1);}
$email=getenv('ADMIN_EMAIL') ?: 'admin@nearbyhand.local';
$stmt=$pdo->prepare("SELECT id FROM admins WHERE email=?");$stmt->execute([$email]);
if($stmt->fetch()){echo "Admin already exists.\n";exit(0);} 
$stmt=$pdo->prepare("INSERT INTO admins(name,email,password_hash) VALUES(?,?,?)");
$stmt->execute(['Main Admin',$email,password_hash($password,PASSWORD_DEFAULT)]);
echo "Main admin created: $email\n";
