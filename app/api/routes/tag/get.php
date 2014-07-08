<?php

/**
 * Get the given tag and its files.
 */

namespace Refiler;

$app->get('/tag/:id.json', function ($id) use ($config) {
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

  try {
    // `TAGS` LEFT JOIN to get the tag data even if there are no files with the
    $rows = $db->fetch_all('
      SELECT
        `TAGS`.`id` AS `tag_id`,
        `TAGS`.`name` AS `tag_name`,
        `TAGS`.`url` AS `tag_url`,
        `TAGS`.`caption` AS `tag_caption`,
        `FILES`.*,
        `DIRS`.`path` AS `dirPath`
      FROM `TAGS`
      LEFT JOIN `FILE_TAG_MAP` ON `TAGS`.`id` = `FILE_TAG_MAP`.`tag_id`
      LEFT JOIN `FILES` ON `FILES`.`id` = `FILE_TAG_MAP`.`file_id`
      LEFT JOIN `DIRS` ON `FILES`.`dir_id` = `DIRS`.`id`
      WHERE `TAGS`.`id` = ?
    ', array($id));

    if (count($rows) === 0) {
      throw new \Exception('Tag not found');
    }

    // get the tag fields from the first row
    $tag_json = array();
    $tag_json['id'] = (int)$rows[0]['tag_id'];
    $tag_json['name'] = $rows[0]['tag_name'];
    $tag_json['url'] = $rows[0]['tag_url'];
    $tag_json['caption'] = $rows[0]['tag_caption'];

    // get tag relatives from the tag map table
    // the keys added are 'parents' and 'children'
    $tag = new Tag($refiler, $tag_json['id']);
    $tag_json = array_merge($tag_json, $tag->get_relatives());

    // get files
    $files = array();
    if (!empty($rows[0]['id'])) { // there is at least 1 file
      $files = array_map(function ($row) {
        // remove tag fields to decrease the amount of data sent
        foreach ($row as $key => $value) {
          if (substr($key, 0, 4) === 'tag_') {
            unset($row[$key]);
          }
        }

        // remove unnecessary dir_id field
        unset($row['dir_id']);

        return $row;
      }, $rows);
    }

    echo json_encode(array(
      'success' => true,
      'tag' => $tag_json,
      'files' => $files
    ));
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
  }
});

?>
