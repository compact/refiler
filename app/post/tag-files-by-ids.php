<?php

/**
 * Tag the given files (by ids) with the given tags (by names).
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

$db = new DB($config['db']);

// auth
$auth = new Auth($config['auth'], $db);
if (!$auth->has_permission('edit')) {
  echo json_encode(array(
    'success' => false,
    'error' => 'Forbidden'
  ));
  exit;
}

// POST data
$file_ids = isset_or($_POST['fileIds']);
$tag_names = isset_or($_POST['tagNames']);
$overwrite = isset_or($_POST['overwrite']);

// sanitize
$file_ids = (array)$file_ids;
$tag_names = Refiler::sanitize_tag_names($tag_names);
$overwrite = (bool)$overwrite;

if (empty($file_ids)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No files selected.'
  ));
  exit;
}

// init
$refiler = new Refiler($config, $db);

$db->begin_transaction();

$tag_count = count($tag_names);

if ($overwrite) {
  if ($tag_count === 0) {
    // delete all existing tags
    $db->query_in('DELETE FROM `FILE_TAG_MAP` WHERE `file_id` IN (%s)',
      $file_ids);
  } else {
    // delete existing tags not in the list of new tags
    $db->query_in('DELETE `FILE_TAG_MAP` FROM `FILE_TAG_MAP`
      JOIN `TAGS` ON `FILE_TAG_MAP`.`tag_id` = `TAGS`.`id`
      JOIN `FILES` ON `FILE_TAG_MAP`.`file_id` = `FILES`.`id`
      WHERE `TAGS`.`name` NOT IN (%s)
      AND `FILES`.`id` IN (%s)',
      array($tag_names, $file_ids));
  }
}

if ($tag_count > 0) {
  // insert tags; existing tags are excluded by ON DUPLICATE KEY UPDATE
  $refiler->insert_tags($tag_names);

  // insert into map
  $db->query_in("INSERT INTO `FILE_TAG_MAP` (`file_id`, `tag_id`)
    SELECT `FILES`.`id`, `TAGS`.`id`
    FROM `FILES`, `TAGS`
    WHERE `TAGS`.`name` IN (%s)
    AND `FILES`.`id` IN (%s)
    ON DUPLICATE KEY UPDATE `FILE_TAG_MAP`.`id` = `FILE_TAG_MAP`.`id`",
    array($tag_names, $file_ids));
}

$db->commit();

echo json_encode(array(
  'success' => true
));

?>