<?php

/**
 * Return all tags.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
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

// get the tags
$rows = $db->fetch_all("
  SELECT
    `TAGS`.*,
    COUNT(DISTINCT `FILE_TAG_MAP`.`id`) AS `fileCount`,
    COUNT(DISTINCT `parents`.`id`) AS `parentCount`,
    COUNT(DISTINCT `children`.`id`) AS `childCount`
  FROM `TAGS`
  LEFT JOIN `FILE_TAG_MAP` ON `TAGS`.`id` = `FILE_TAG_MAP`.`tag_id`
  LEFT JOIN `TAG_MAP` AS `parents` ON `TAGS`.`id` = `parents`.`child_id`
  LEFT JOIN `TAG_MAP` AS `children` ON `TAGS`.`id` = `children`.`parent_id`
  GROUP BY `TAGS`.`id`
  ORDER BY `TAGS`.`name`
");

// typecasting
$arrays = array_map(function ($row) {
  return array(
    'id' => (int)$row['id'],
    'name' => $row['name'],
    'url' => $row['url'],
    'caption' => $row['caption'],
    'fileCount' => (int)$row['fileCount'],
    'parentCount' => (int)$row['parentCount'],
    'childCount' => (int)$row['childCount']
  );
}, $rows);

echo json_encode(array(
  'success' => true,
  'tags' => $arrays
));

?>