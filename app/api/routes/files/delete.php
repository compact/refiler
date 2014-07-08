<?php

/**
 * Delete the given files.
 */

namespace Refiler;

$app->delete('/files/:ids.json', function ($ids) use ($config) {
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
  $ids = array_map('intval', explode(',', $ids));

  if (empty($ids)) {
    echo json_encode(array(
      'success' => false,
      'error' => 'No files selected.'
    ));
    exit;
  }

  // init
  $refiler = new Refiler($config, $db);

  foreach ($ids as $id) {
    try {
      // get the file
      $file = new File($refiler, $id);

      // delete the file
      $file->delete();
    } catch (\Exception $e) {
    }
  }

  echo json_encode(array(
    'success' => true
  ));
});

?>
