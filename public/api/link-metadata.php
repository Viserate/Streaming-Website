<?php
require_once __DIR__ . '/../_bootstrap.php';
require_admin();
header('Content-Type: application/json; charset=utf-8');
$url = $_GET['url'] ?? $_POST['url'] ?? '';
if (!$url) { echo json_encode(['success'=>0]); exit; }
echo json_encode(['success'=>1, 'meta'=>['title'=>$url, 'description'=>'', 'image'=>['url'=>'']]]);