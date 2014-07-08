<?php

/**
 * Login with the given credentials. If successful, output the user's
 *   permissions.
 */

namespace Refiler;

$app->post('/session.json', function () use ($app, $config) {
  // POST data
  $email =$app->request->post('email');
  $password = $app->request->post('password');

  // init
  $db = new DB($config['db']);
  $auth = new Auth($config['auth'], $db);
  $refiler = new Refiler($config, $db);

  try {
    // login
    $user = Sentry::authenticate(array(
      'email'    => $email,
      'password' => $password,
    ), true); // remember

    // update our Auth object
    $auth->set_user($user);

    // start building the result to output
    $result = array(
      'success' => true,
      'user' => $auth->get_array()
    );

    // if the user's view permission is different from the default view permission
    // for guests, then output tags and dirs (for example, if the user has view
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
