<?php

/**
 * Given file ids, return the tags of each file.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

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

// POST data
$ids = isset_or($_POST['ids']);

// sanitize
$ids = (array)$ids;

if (empty($ids)) {
  echo json_encode(array(
    'success' => true
  ));
}

$refiler = new Refiler($config, $db);

$count = count($ids);
$rows = $db->fetch_all_in("SELECT `FILE_TAG_MAP`.`file_id`,
  `TAGS`.`url`, `TAGS`.`name`
  FROM `TAGS` JOIN `FILE_TAG_MAP` ON `TAGS`.`id` = `FILE_TAG_MAP`.`tag_id`
  WHERE `FILE_TAG_MAP`.`file_id` IN (%s)", $ids);

$tags = array();
foreach ($rows as $row) {
  $tags[$row['file_id']][] = array(
    'url' => $row['url'],
    'name' => $row['name']
  );
}

echo json_encode(array(
  'success' => true,
  'tags' => $tags
));

?>