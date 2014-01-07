<?php

/**
 * Delete the file with the given id.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// GET params
$id = isset_or($_GET['id']);

// sanitize
$id = (int)$id;

if (empty($id)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No file given'
  ));
  exit;
}

// init
$db = new DB($config['db']);
$refiler = new Refiler($config, $db);

// get the file
try {
  $file = new File($refiler, $id);
} catch (\Exception $e) {
  echo json_encode(array(
    'success' => false,
    'error' => $e->getMessage()
  ));
  exit;
}

// delete the file
$file->delete();

echo json_encode(array(
  'success' => true
));;

?>