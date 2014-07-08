<?php

/**
 * Get the given dir and the files in it.
 */

namespace Refiler;

$app->get('/dir/:id.json', function ($id) use ($app, $config) {
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
  $update = (bool)$app->request->get('update');

  // sanitize
  $id = (int)$id;

  $refiler = new Refiler($config, $db);

  // get the dir
  try {
    $dir = new Dir($refiler, $id);
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
    exit;
  }

  // verify that the dir exists in the filesystem
  if (!$dir->exists_in_filesystem()) {
    echo json_encode(array(
      'success' => false,
      'error' => 'Folder not found'
    ));
    exit;
  }

  // optionally update the dir's file rows
  if ($update) {
    $dir->update(array(
      'log' => false,
      'recursive' => false,
      'deep_update' => true,
      'create_thumbs' => true,
      'file_limit' => -1,
      'query_limit' => -1
    ));
  }

  // get the files in the dir
  $path = $dir->get_path();
  $file_arrays = array_map(function ($row) use ($path) {
    unset($row['dir_id']);
    $row['dirPath'] = $path;

    return $row;
  }, $dir->get_file_rows());

  // output
  echo json_encode(array(
    'success' => true,
    'files' => $file_arrays,
    'dir' => $dir->get_array()
  ));
});

?>
