<?php

/**
 * Output the email of the user with the given activation code.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// GET params
$activationCode = isset_or($_GET['activationCode']);

// init
$db = new DB($config['db']);
new Auth($config['auth'], $db);

// find user
try {
  $user = Sentry::getUserProvider()->findByActivationCode(
    $activationCode
  );

  echo json_encode(array(
    'success' => true,
    'email' => $user->email
  ));
} catch (\Exception $e) {
  echo json_encode(array(
    'success' => false,
    'error' => $e->getMessage()
  ));
}

?>