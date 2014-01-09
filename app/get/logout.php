<?php

/**
 * Logout the current user.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
$db = new DB($config['db']);
$auth = new Auth($config['auth'], $db);
$refiler = new Refiler($config, $db);

// logout; this method does not throw exceptions
Sentry::logout();

// start building the result to output
$result = array(
  'success' => true
);

// if the default guest view permission is different from the user's view
// permission, then output tags and dirs (for example, if the user has view
// permission but guests do not, then output empty arrays to clear the tag and
// dir navigation)
$guest_view_permission = $config['auth']['guest_permissions']['view'];
$user_view_permission = $auth->has_permission('view');
if ($guest_view_permission !== $user_view_permission) {
  $result['tags'] = $guest_view_permission
    ? $refiler->get_tags_array()
    : array();
  $result['dirs'] = $guest_view_permission
    ? $refiler->get_dirs_array()
    : array();
}

// output the result
echo json_encode($result);

?>