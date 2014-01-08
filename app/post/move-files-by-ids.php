<?php

/**
 * Move the given files (by ids) to the given dir (by path).
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
$ids = isset_or($_POST['fileIds']);
$dir_id = isset_or($_POST['dirId']);

// sanitize
$ids = (array)$ids; // the individual ids are sanitized below
$dir_id = (int)$dir_id;

if (empty($ids) || empty($dir_id)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No files or folder selected.'
  ));
  exit;
}

// init
$refiler = new Refiler($config, $db);
$dir = new Dir($refiler, $dir_id);

foreach ($ids as $id) {
  // sanitize
  $id = (int)$id;

  $file = new File($refiler, $id);

  // move the file
  try {
    // don't update the row here (third argument); update all rows with one
    // query below
    $file->move($dir->get_path(), $file->get_name(), false);
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
    exit;
  }
}

$count = count($ids);
$db->query_in("
  UPDATE `FILES`
  SET `dir_id` = $dir_id
  WHERE `id` IN (%s)
  LIMIT $count
", $ids);

echo json_encode(array(
  'success' => true
));

?>