<?php

/**
 * Create the first admin account and login to it. Run this script on
 *   installation.
 */

// edit these values for the admin login
$email = 'email@example.com';
$password = 'samplepassword';

// init
namespace Refiler;
require '../require.php';
$db = new DB($config['db']);
$auth = new Auth($config['auth'], $db);

// only proceed if there are 0 users
$users = Sentry::findAllUsers();
if (count($users) > 0) {
  echo 'A user has already been created.';
  exit;
}

// create a new user
try {
  $user = Sentry::createUser(array(
    'email' => $email,
    'password' => $password,
    'activated' => true,
    'permissions' => array(
      'view' => 1,
      'edit' => 1,
      'admin' => 1
    )
  ));

  // login
  Sentry::login($user, true); // remember

  echo "Created admin $email and logged in.";
} catch (\Exception $e) {
  echo $e->getMessage();
}