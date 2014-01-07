<?php

/**
 * Output all users.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
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

// output result
echo json_encode(array(
  'success' => true,
  'users' => $auth->get_user_arrays()
));

?>