<?php

/**
 * Delete the given dir (by id).
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

$db = new DB($config['db']);

// auth
$auth = new Auth($config['auth'], $db);
if (!$auth->has_permission('admin')) {
  echo json_encode(array(
    'success' => false,
    'error' => 'Forbidden'
  ));
  exit;
}

// GET params
$id = isset_or($_GET['id']);

// sanitize
$id = (int)$id;

if (empty($id)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No folder provided'
  ));
  exit;
}

// get the dir
$refiler = new Refiler($config, $db);
$dir = new Dir($refiler, $id);

// delete the dir
$dir->delete();

echo json_encode(array(
  'success' => true
));

?>