<?php

/**
 * Output a JSON object containing all files in the given dir.
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
$id = isset_or($_GET['id']);

// sanitize
$id = (int)$id;

// get the dir
$dir = new Dir($refiler, $id);

if (!$dir->exists_in_filesystem()) {
  echo json_encode(array(
    'success' => false,
    'error' => 'Folder not found'
  ));
  exit;
}

// update the dir
$dir->update(array(
  'log' => false,
  'recursive' => false,
  'deep_update' => true,
  'create_thumbs' => true,
  'file_limit' => -1,
  'query_limit' => -1
));

echo json_encode(array(
  'success' => true
));

?>