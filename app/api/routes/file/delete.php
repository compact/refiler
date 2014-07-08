<?php

/**
 * Delete the given file.
 */

namespace Refiler;

$app->delete('/file/:id.json', function ($id) use ($config) {
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
});

?>
