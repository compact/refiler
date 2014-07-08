<?php

/**
 * Delete the given user.
 */

namespace Refiler;

$app->delete('/user/:id.json', function ($id) use ($config) {
  $db = new DB($config['db']);

  // auth
  $auth = new Auth($config['auth'], $db);
  if (!$auth->has_permission('admin')) {
    echo json_encode(array(
      'success' => false,
      'error' => 'Forbidden'
    ));
    exit;
  }

  // sanitize
  $id = (int)$id;

  try {
    // find the user
    $user = Sentry::findUserById($id);

    // delete the user
    $user->delete();

    echo json_encode(array(
      'success' => true
    ));
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
  }
});

?>
