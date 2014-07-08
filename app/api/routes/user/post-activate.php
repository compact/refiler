<?php

/**
 * Activate the user account with the given activation code.
 */

namespace Refiler;

$app->post('/user/:id/activate.json', function ($id) use ($app, $config) {
  // POST data; $_POST['email'] is set, but is uneditable and not used because
  // only admins can change user emails
  $password = $app->request->post('password');
  $activationCode = $app->request->post('activationCode');

  // init
  $db = new DB($config['db']);
  $auth = new Auth($config['auth'], $db);
  $refiler = new Refiler($config, $db);

  try {
    // get the user
    $user = Sentry::getUserProvider()->findByActivationCode($activationCode);

    // activate
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

    // update our Auth object
    $auth->set_user($user);

    // start building the result to output
    $result = array(
      'success' => true,
      'user' => $auth->get_array()
    );

    // if the user's view permission is different from the default guest view
    // permission, then output tags and dirs (for example, if the user has view
    // permission and guests do not, they can now view tags and dirs)
    $view_permission = $result['user']['permissions']['view'];
    if ($config['auth']['guest_permissions']['view'] !== $view_permission) {
      $result['tags'] = $view_permission ? $refiler->get_tags_array() : array();
      $result['dirs'] = $view_permission ? $refiler->get_dirs_array() : array();
    }

    // output the result
    echo json_encode($result);
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
  }
});

?>
