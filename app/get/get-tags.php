<?php

/**
 * Return all tags.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
$db = new DB($config['db']);

// get the tags
$rows = $db->fetch_all("
  SELECT
    `TAGS`.*,
    COUNT(DISTINCT `FILE_TAG_MAP`.`id`) AS `fileCount`,
    COUNT(DISTINCT `children`.`id`) AS `childCount`,
    COUNT(DISTINCT `parents`.`id`) AS `parentCount`
  FROM `TAGS`
  LEFT JOIN `FILE_TAG_MAP` ON `TAGS`.`id` = `FILE_TAG_MAP`.`tag_id`
  LEFT JOIN `TAG_MAP` AS `children` ON `TAGS`.`id` = `children`.`parent_id`
  LEFT JOIN `TAG_MAP` AS `parents` ON `TAGS`.`id` = `parents`.`child_id`
  GROUP BY `TAGS`.`id`
  ORDER BY `TAGS`.`name`
");

echo json_encode(array(
  'success' => true,
  'tags' => $rows
));

?>