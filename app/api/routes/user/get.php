<?php

/**
 * Get the user with the given activation code.
 */

namespace Refiler;

$app->get('/user/:code.json', function ($activation_code) use ($config) {
  // init
  $db = new DB($config['db']);
  new Auth($config['auth'], $db);

  try {
    // find the user
    $user = Sentry::getUserProvider()->findByActivationCode(
      $activation_code
    );

    echo json_encode(array(
      'success' => true,
      'user' => array(
        'id' => $user->getId(),
        'email' => $user->email
      )
    ));
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
  }
});

?>
