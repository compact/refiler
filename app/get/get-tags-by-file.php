<?php

/**
 * Return the tag names of the given file.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// GET params
$id = isset_or($_GET['id']);

// sanitize
$id = (int)$id;

if (empty($id)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No tag given'
  ));
  exit;
}

// init
$db = new DB($config['db']);
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

?>