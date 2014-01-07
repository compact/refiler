<?php

/**
 * Login with the given credentials. If successful, output the user's
 *   permissions.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// POST data
$email = isset_or($_POST['email']);
$password = isset_or($_POST['password']);

// init
$db = new DB($config['db']);
$auth = new Auth($config['auth'], $db);
$refiler = new Refiler($config, $db);

// auth
try {
  $user = Sentry::authenticate(array(
    'email'    => $email,
    'password' => $password,
  ), true); // remember
  $auth->set_user($user);

  echo json_encode(array(
    'success' => true,
    'user' => array(
      'permissions' => $auth->get_permissions()
    )
  ));
} catch (\Exception $e) {
  echo json_encode(array(
    'success' => false,
    'error' => $e->getMessage()
  ));
}

?>