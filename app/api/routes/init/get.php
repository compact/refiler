<?php

namespace Refiler;

$app->get('/init.json', function () use ($config) {
  $db = new DB($config['db']);
  $auth = new Auth($config['auth'], $db);
  $refiler = new Refiler($config, $db);

  // get the user array
  $user_array = $auth->get_array();

  // this is a more efficient way of getting the view permission than calling
  // $auth->has_permission('view')
  $view_permission = $user_array['permissions']['view'];

  // output
  echo json_encode(array(
    'success' => true,
    'user' => $user_array,
    'tags' => $view_permission ? $refiler->get_tags_array() : array(),
    'dirs' => $view_permission ? $refiler->get_dirs_array() : array()
  ));
});

?>
