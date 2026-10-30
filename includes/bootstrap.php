<?php
session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
session_start();
require_once __DIR__.'/../config/database.php';

function e($value) { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function csrf_token() { if (empty($_SESSION['csrf'])) $_SESSION['csrf']=bin2hex(random_bytes(32)); return $_SESSION['csrf']; }
function csrf_verify() { if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Security token expired. Please refresh and try again.'); } }
function current_user() { return $_SESSION['user'] ?? null; }
function require_role($role) { $u=current_user(); if (!$u || $u['role']!==$role) { header('Location: login.php?notice=login_required'); exit; } }
function flash($text=null, $type='success') { if ($text!==null) $_SESSION['flash']=[$text,$type]; elseif (!empty($_SESSION['flash'])) { $f=$_SESSION['flash']; unset($_SESSION['flash']); return $f; } return null; }
function redirect($path) { header('Location: '.$path); exit; }
