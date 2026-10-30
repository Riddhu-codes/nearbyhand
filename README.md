# NearbyHand

NearbyHand is a PHP/MySQL home-service booking platform for Plumbing and Electrician services.

## Requirements
- PHP 8+
- MySQL/MariaDB
- XAMPP Apache and MySQL

## Setup
1. Start Apache and MySQL in XAMPP.
2. Import `database/schema.sql` with phpMyAdmin.
3. If needed, set `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS` environment variables.
4. From PowerShell run:
   ```powershell
   $env:ADMIN_PASSWORD='UseAStrongPasswordHere'
   $env:ADMIN_EMAIL='admin@nearbyhand.local'
   C:\xampp\php\php.exe database\seed.php
   ```
5. Open `http://localhost/dashboard/nearbyhand/`.

## Login roles
- Customers register at `register.php`.
- Professionals apply at `worker-register.php` and are approved in the Admin panel.
- The single admin is created by `database/seed.php`; no public admin registration is provided.

## Included workflows
Homepage and services, secure registration/login, worker applications, customer booking with address, saved bookings, worker assignment and job status updates, admin approval, and database-backed service pricing. Additional gateway OTP, invoice, review, coupon and report integrations require external credentials or follow-up implementation.

## Production security
Use HTTPS, strong unique database credentials, set PHP production display settings off, restrict `config/` and `database/` via the web server, and configure a real payment provider using server-side signature verification. Do not commit secrets.
