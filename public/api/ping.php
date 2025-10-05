<?php
header('Content-Type: application/json');
echo json_encode(['pong'=>true,'file'=>__FILE__,'cwd'=>getcwd(),'t'=>date('c')]);
