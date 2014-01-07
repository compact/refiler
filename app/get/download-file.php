<?php

/**
 * Download the file at the given path.
 */

namespace Refiler;
require '../require.php';

// GET params
$path = isset_or($_GET['path']);

// sanitize
$path = Dir::sanitize_path($path);

if (!is_file($path)) {
  exit('File not found.');
}

$name = after($path, '/');

header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename=\"$name\"");
header("Content-Transfer-Encoding: Binary"); 

readfile($path);

?>