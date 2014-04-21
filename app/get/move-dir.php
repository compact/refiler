<?php

/**
 * Move the given dir (by id) to the given path.
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

// GET params
$id = isset_or($_GET['id']);
$path = isset_or($_GET['path']);

// sanitize; $path gets sanitized in move() below
$id = (int)$id;

// get the dir
$refiler = new Refiler($config, $db);
$dir = new Dir($refiler, $id);

// move the dir
try {
  $dir->move($path);

  echo json_encode(array(
    'success' => true,
    'dir' => $dir->get_array()
  ));
} catch (\Exception $e) {
  echo json_encode(array(
    'success' => false,
    'error' => $e->getMessage()
  ));
}

?>