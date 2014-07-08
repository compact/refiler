<?php

/**
 * Delete the given tag.
 */

namespace Refiler;

$app->delete('/tag/:id.json', function ($id) use ($config) {
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

  // get the tag
  try {
    $tag = new Tag($refiler, $id);
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
    exit;
  }

  // delete the tag
  $tag->delete();

  echo json_encode(array(
    'success' => true
  ));
});

?>
