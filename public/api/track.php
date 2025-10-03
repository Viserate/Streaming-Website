<?php
require_once __DIR__ . '/../../config/pdo.php';
require_once __DIR__ . '/../../config/auth.php';

header('Content-Type: application/json; charset=utf-8');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false]); exit; }

$origin=$_SERVER['HTTP_ORIGIN']??''; $host=$_SERVER['HTTP_HOST']??'';
if($origin && parse_url($origin, PHP_URL_HOST)!==$host){ http_response_code(403); echo json_encode(['ok'=>false]); exit; }

$input=file_get_contents('php://input'); $data=[];
if($input){ $tmp=json_decode($input,true); if(json_last_error()===JSON_ERROR_NONE) $data=$tmp; }
if(!$data) $data=$_POST;

$event=(string)($data['event']??''); $allowed=['page_view','video_watch','time_spent'];
if(!in_array($event,$allowed,true)){ echo json_encode(['ok'=>false]); exit; }

$video_id=isset($data['video_id'])?(int)$data['video_id']:null;
$duration=isset($data['duration'])?(int)$data['duration']:null;
if($event==='time_spent'){ if($duration<0) $duration=0; if($duration>7200) $duration=7200; }

if(session_status()!==PHP_SESSION_ACTIVE) session_start();
if(empty($_SESSION['sid'])) $_SESSION['sid']=bin2hex(random_bytes(16));
$session_id=$_SESSION['sid'];

$user=current_user(); $user_id=$user['id']??null;
$ua=substr($_SERVER['HTTP_USER_AGENT']??'',0,255); $ip=$_SERVER['REMOTE_ADDR']??'';

$pdo=db();
$stmt=$pdo->prepare("INSERT INTO analytics_events (session_id,user_id,event_type,video_id,duration_seconds,user_agent,ip_addr) VALUES (?,?,?,?,?,?,?)");
$stmt->execute([$session_id,$user_id,$event,$video_id,$duration,$ua,$ip]);
echo json_encode(['ok'=>true]);
