<?php

/**
 * Move the given dir (by id) to the given path.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// GET params
$id = isset_or($_GET['id']);
$path = isset_or($_GET['path']);

// sanitize; $path gets sanitized in move() below
$id = (int)$id;

// init
$db = new DB($config['db']);
$refiler = new Refiler($config, $db);

// get the dir
$dir = new Dir($refiler, $id);

// move the dir
try {
  $dir->move($path);

  echo json_encode(array(
    'success' => true,
    'dir' => array(
      'path' => $dir->get_path()
    )
  ));
} catch (\Exception $e) {
  echo json_encode(array(
    'success' => false,
    'error' => $e->getMessage()
  ));
}

?>