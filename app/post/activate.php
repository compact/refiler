<?php

/**
 * Activate the user account with the given activation code.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// POST data; $_POST['email'] is set, but is uneditable and  not used because
// only admins can change user emails
$password = isset_or($_POST['password']);
$activationCode = isset_or($_POST['activationCode']);

// init
$db = new DB($config['db']);
$auth = new Auth($config['auth'], $db);
$refiler = new Refiler($config, $db);

try {
  // find user
  $user = Sentry::getUserProvider()->findByActivationCode(
    $activationCode
  );

  // attempt activation
  $success = $user->attemptActivation($activationCode);
  if (!$success) {
    throw new \Exception('User activation failed.');
  }

  // update the password
  $user->password = $password;
  $success = $user->save();
  if (!$success) {
    throw new \Exception("Password change failed.");
  }

  // login
  Sentry::login($user, true); // remember

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