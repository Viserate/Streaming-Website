<?php
function db() {
  static $pdo=null;
  if ($pdo) return $pdo;
  $cfg = require __DIR__ . '/db.php';
  $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset={$cfg['charset']}";
  $opt = [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
  ];
  try { $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $opt); }
  catch (PDOException $e){ http_response_code(500); die("DB connection failed: ".htmlspecialchars($e->getMessage())); }
  return $pdo;
}
