<?php

/**
 * Output the files in the given dir.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

$db = new DB($config['db']);

// auth
$auth = new Auth($config['auth'], $db);
if (!$auth->has_permission('view')) {
  echo json_encode(array(
    'success' => false,
    'error' => 'Forbidden'
  ));
  exit;
}

// GET params
$path = isset_or($_GET['path']); // gets sanitized in the Dir constructor

$refiler = new Refiler($config, $db);

// get the dir
$dir = new Dir($refiler, $path);
if (!$dir->exists_in_filesystem()) {
  echo json_encode(array(
    'success' => false,
    'error' => 'Folder not found'
  ));
  exit;
}
$path = $dir->get_path(); // sanitized

// get the files in the dir
$file_arrays = array_map(function ($row) use ($path) {
  unset($row['dir_id']);
  $row['dirPath'] = $path;

  return $row;
}, $dir->get_file_rows());

// output
echo json_encode(array(
  'success' => true,
  'files' => $file_arrays,
  'dir' => array(
    'id' => $dir->get_id(),
    'path' => $path
  )
));

?>