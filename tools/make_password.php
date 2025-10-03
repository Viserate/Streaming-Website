<?php
$pwd = $argv[1] ?? 'change_me';
echo password_hash($pwd, PASSWORD_DEFAULT), PHP_EOL;
