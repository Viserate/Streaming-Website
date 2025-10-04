<?php
$docroot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
$home    = $docroot ? dirname($docroot) : __DIR__ . '/..';
$base    = getenv('STORAGE_BASE') ?: ($home . '/SiteStorage');

define('STORAGE_BASE', $base);
define('MEDIA_DIR', getenv('MEDIA_DIR') ?: (STORAGE_BASE . '/media'));
define('VIDEO_DIR', getenv('VIDEO_DIR') ?: (STORAGE_BASE . '/video'));

define('MEDIA_URL', getenv('MEDIA_URL') ?: '/media');
define('VIDEO_URL', getenv('VIDEO_URL') ?: '/video');

@mkdir(STORAGE_BASE, 0755, true);
@mkdir(MEDIA_DIR, 0755, true);
@mkdir(VIDEO_DIR, 0755, true);

$pubMedia = $docroot . '/media';
$pubVideo = $docroot . '/video';
if (!is_link($pubMedia) && !is_dir($pubMedia) && is_dir(MEDIA_DIR)) { @symlink(MEDIA_DIR, $pubMedia); }
if (!is_link($pubVideo) && !is_dir($pubVideo) && is_dir(VIDEO_DIR)) { @symlink(VIDEO_DIR, $pubVideo); }