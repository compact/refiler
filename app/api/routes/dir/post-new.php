<?php

/**
 * Create a new dir at the given path.
 */

namespace Refiler;

$app->post('/dir.json', function () use ($app, $config) {
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

  // POST data
  $path = $app->request->post('path'); // gets sanitized in the Dir constructor

  // construct the dir
  $dir = new Dir($refiler, $path);
  $path = $dir->get_path();

  // create the dir and insert a row
  $success = $dir->create_and_insert();

  if ($success) {
    $dir_array = $dir->get_array();
    $dir_array['subdirs'] = array();

    echo json_encode(array(
      'success' => true,
      'dir' => $dir_array
    ));
  } else {
    echo json_encode(array(
      'success' => false,
      'error' => "Failed to create or insert the folder /$path."
    ));
  }
});

?>
