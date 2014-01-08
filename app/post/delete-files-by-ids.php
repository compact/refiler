<?php

/**
 * Delete the given files (by ids).
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

// POST data
$ids = isset_or($_POST['ids']);

// sanitize
$ids = (array)$ids; // the individual ids are sanitized below

if (empty($ids)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No files selected.'
  ));
  exit;
}

$refiler = new Refiler($config, $db);

foreach ($ids as $id) {
  // sanitize
  $id = (int)$id;

  // get the file
  $file = new File($refiler, $id);

  // delete the file
  $file->delete();
}

echo json_encode(array(
  'success' => true
));

?>