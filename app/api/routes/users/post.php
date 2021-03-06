<?php

/**
 * Edit users.
 */

namespace Refiler;

$app->post('/users.json', function () use ($app, $config) {
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

  $refiler = new Refiler($config, $db);

  // POST data
  $users = $app->request->post('users');

  try {
    foreach ($users as $user_data) {
      // sanitize
      $id = isset_or($user_data['id']);
      $email = isset_or($user_data['email']);
      $permissions = Auth::decode_permissions(
        (array)isset_or($user_data['permissions'])
      );

      if (empty($email)) {
        // email is required for login
        continue;
      }

      if ($id !== null) {
        // update existing user
        $id = (int)$id;

        $user = Sentry::findUserById($id);
        $user->email = $email;
        $user->permissions = $permissions;

        $success = $user->save();
        if (!$success) {
          throw new \Exception("Failed to update user {$user_data['email']}");
        }
      } else {
        // create new user
        Sentry::register(array(
          'email' => $email,
          'password' => 'placeholder_password_does_not_work_before_activation',
          'permissions' => $permissions
        ));
      }
    }

    echo json_encode(array(
      'success' => true,
      'users' => $auth->get_user_arrays()
    ));
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
  }
});

?>
