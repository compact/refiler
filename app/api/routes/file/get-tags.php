<?php

/**
 * Get the tags of the given file.
 */

namespace Refiler;

$app->get('/file/:id/tags.json', function ($id) use ($config) {
  $db = new DB($config['db']);

  // auth
  $auth = new Auth($config['auth'], $db);
  if (!$auth->has_permission('view')) {
    echo json_encode(array(
      'success' => false,
      'error' => 'Forbidden'
    ));
    exit;
  }

  // sanitize
  $id = (int)$id;

  $refiler = new Refiler($config, $db);

  // find the tags
  $rows = $db->fetch_all('
    SELECT `TAGS`.`url`, `TAGS`.`name`
    FROM `TAGS` JOIN `FILE_TAG_MAP`
    ON `TAGS`.`id` = `FILE_TAG_MAP`.`tag_id`
    WHERE `FILE_TAG_MAP`.`file_id` = ?
    ORDER BY `TAGS`.`name`
  ', array($id));

  echo json_encode(array(
    'success' => true,
    'tags' => $rows
  ));
});

?>
