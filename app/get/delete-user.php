<?php

/**
 * Delete the given user.
 */
namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// GET params
$id = isset_or($_GET['id']);

// sanitize
$id = (int)$id;

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


?>