<?php

/**
 * Delete the given dir.
 */

namespace Refiler;

$app->delete('/dir/:id.json', function ($id) use ($config) {
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
});

?>
