<?php

/**
 * Output the logged-in-ness and permissions of the current user.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
$db = new DB($config['db']);
$auth = new Auth($config['auth'], $db);

// output result
echo json_encode(array(
  'success' => true,
  'user' => array(
    'loggedIn' => $auth->logged_in(),
    'permissions' => $auth->get_permissions()
  )
));

?>