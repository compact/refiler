<?php

/**
 * Download the file at the given path. TODO
 */

namespace Refiler;

$app->get('/file/:id/download', function ($id) use ($app, $config) {
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

  // set headers
  $app->response->headers->set('Content-Type', 'application/octet-stream');
  $app->response->headers->set('Content-Disposition',
    "attachment; filename=\"{$file->get_name()}\"");
  $app->response->headers->set('Content-Transfer-Encoding', 'Binary'); 

  readfile($path);
});

?>
