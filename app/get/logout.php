<?php

/**
 * Logout the current user.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
$db = new DB($config['db']);
new Auth($config['auth'], $db);

// logout; this method does not throw exceptions
Sentry::logout();

echo json_encode(array(
  'success' => true
));

?>