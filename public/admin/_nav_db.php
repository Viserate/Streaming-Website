<?php
if (!function_exists('admin_nav_ensure')) {
  function admin_nav_ensure(PDO $pdo) {
    try {
      $pdo->exec("CREATE TABLE IF NOT EXISTS admin_nav_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        label VARCHAR(255) NOT NULL,
        href VARCHAR(1024) NOT NULL DEFAULT '#',
        parent_id INT NULL,
        position INT NOT NULL DEFAULT 1,
        visible TINYINT(1) NOT NULL DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (parent_id, position)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Throwable $e) {}
  }

  function admin_nav_seed_defaults(PDO $pdo) {
    $exists = $pdo->query("SELECT COUNT(*) FROM admin_nav_items")->fetchColumn();
    if ($exists > 0) return;
    $ins = $pdo->prepare("INSERT INTO admin_nav_items (label, href, parent_id, position, visible) VALUES (?,?,?,?,1)");
    $pos = 1;

    $ins->execute(['Dashboard','/admin/',null,$pos++]);
    $ins->execute(['Pages','/admin/pages/',null,$pos++]);
    $ins->execute(['Videos','#',null,$pos++]); $idVideos = $pdo->lastInsertId();
    $ins->execute(['Media','/admin/media/',null,$pos++]);
    $ins->execute(['Users','/admin/users/',null,$pos++]);
    $ins->execute(['Analytics','/admin/analytics.php',null,$pos++]);
    $ins->execute(['Settings','#',null,$pos++]); $idSettings = $pdo->lastInsertId();
    $ins->execute(['Tools','#',null,$pos++]); $idTools = $pdo->lastInsertId();

    $cpos=1;
    $ins->execute(['All Videos','/admin/videos/',$idVideos,$cpos++]);
    $ins->execute(['Upload','/admin/videos/upload.php',$idVideos,$cpos++]);
    $ins->execute(['Upload (Large)','/admin/videos/upload_large.php',$idVideos,$cpos++]);
    $ins->execute(['External / Embed','/admin/videos/add_external.php',$idVideos,$cpos++]);
    $ins->execute(['Categories','/admin/videos/categories.php',$idVideos,$cpos++]);
    $ins->execute(['Playlists','/admin/videos/playlists.php',$idVideos,$cpos++]);
    $ins->execute(['Scan Library','/admin/videos/scan.php',$idVideos,$cpos++]);

    $cpos=1;
    $ins->execute(['General','/admin/settings/general.php',$idSettings,$cpos++]);
    $ins->execute(['Branding','/admin/settings/branding.php',$idSettings,$cpos++]);
    $ins->execute(['Navigation (DB)','/admin/settings/navigation_db.php',$idSettings,$cpos++]);

    $cpos=1;
    $ins->execute(['Export JSON','/admin/tools/export.php',$idTools,$cpos++]);
    $ins->execute(['Import JSON','/admin/tools/import.php',$idTools,$cpos++]);
    $ins->execute(['System Info','/admin/tools/system.php',$idTools,$cpos++]);
    $ins->execute(['PHP Info','/tools/phpinfo.php',$idTools,$cpos++]);
    $ins->execute(['DB Migrate','/admin/tools/db_migrate.php',$idTools,$cpos++]);
  }

  function admin_nav_fetch(PDO $pdo) {
    $rows = $pdo->query("SELECT * FROM admin_nav_items WHERE visible=1 ORDER BY COALESCE(parent_id,0), position, id")->fetchAll(PDO::FETCH_ASSOC);
    $byParent = [];
    foreach ($rows as $r) {
      $pid = $r['parent_id'] ? (int)$r['parent_id'] : 0;
      $byParent[$pid][] = $r;
    }
    $tree = [];
    foreach ($byParent[0] ?? [] as $top) {
      $top['children'] = $byParent[(int)$top['id']] ?? [];
      $tree[] = $top;
    }
    return $tree;
  }

  function admin_nav_resequence(PDO $pdo, $parentId) {
    $pid = $parentId ? (int)$parentId : null;
    $st = $pid ? $pdo->prepare("SELECT id FROM admin_nav_items WHERE parent_id=? ORDER BY position, id") 
              : $pdo->prepare("SELECT id FROM admin_nav_items WHERE parent_id IS NULL ORDER BY position, id");
    $st->execute($pid ? [$pid] : []);
    $ids = $st->fetchAll(PDO::FETCH_COLUMN);
    $pos = 1;
    $up = $pdo->prepare("UPDATE admin_nav_items SET position=? WHERE id=?");
    foreach ($ids as $id) $up->execute([$pos++, $id]);
  }
}