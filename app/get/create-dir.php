<?php

/**
 * Create a new dir at the given path.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

$db = new DB($config['db']);

// auth
$auth = new Auth($config['auth'], $db);
if (!$auth->has_permission('edit')) {
  echo json_encode(array(
    'success' => false,
    'error' => 'Forbidden'
  ));
  exit;
}

$refiler = new Refiler($config, $db);

// GET params
$path = isset_or($_GET['path']); // gets sanitized in the Dir constructor

// construct the dir
$dir = new Dir($refiler, $path);
$path = $dir->get_path();

// create the dir and insert a row
$success = $dir->create_and_insert();

if ($success) {
  echo json_encode(array(
    'success' => true,
    'path' => $path
  ));
} else {
  echo json_encode(array(
    'success' => false,
    'error' => "Failed to create or insert the folder /$path."
  ));
}

?>