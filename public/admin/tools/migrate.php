<?php
require __DIR__ . '/../_bootstrap.php';
try { $sql=file_get_contents($_SERVER['DOCUMENT_ROOT'].'/tools/sql/admin_v7.sql'); db()->exec($sql); echo 'OK'; }
catch(Throwable $e){ http_response_code(500); echo 'ERROR: '.$e->getMessage(); }
