<?php
require_once __DIR__ . '/pdo.php';
if (session_status() === PHP_SESSION_NONE) session_start();

function csrf_token(){ if(empty($_SESSION['csrf_token'])) $_SESSION['csrf_token']=bin2hex(random_bytes(32)); return $_SESSION['csrf_token']; }
function csrf_check($t){ return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$t); }

function current_user(){ return $_SESSION['user'] ?? null; }
function require_login(){ if(!current_user()){ header('Location: /login/'); exit; } }
function require_admin(){ require_login(); if((current_user()['role']??'')!=='admin'){ http_response_code(403); echo 'Forbidden'; exit; } }
