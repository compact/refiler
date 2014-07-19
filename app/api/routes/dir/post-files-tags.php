<?php

/**
 * Tag the files in the given dir with the given tags. Optionally, tag the
 *   files in all recursive subdirs of the given dir. Optionally, overwrite
 *   all existing tags with the given tags.
 */

namespace Refiler;

$app->post('/dir/:id/files/tags.json', function ($dir_id) use ($app, $config) {
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
  $tag_names = $app->request->post('tagNames');
  $recursive = $app->request->post('recursive');
  $overwrite = $app->request->post('overwrite');

  // sanitize
  $dir_id = (int)$dir_id;
  $tag_names = Refiler::sanitize_tag_names($tag_names);
  $recursive = (bool)$recursive;
  $overwrite = (bool)$overwrite;

  // init
  $refiler = new Refiler($config, $db);
  $db->begin_transaction();

  // get existing common tags
  #$rows = $dir->get_common_tag_rows();



  $tag_count = count($tag_names);



  // get dir ids
  $dir_ids = array($dir_id);
  if ($recursive) {
    // recursive case
    try {
      // get the dir
      $dir = new Dir($refiler, $dir_id);

      // recursive, not including $dir
      $subdirs = $dir->get_subdirs(true, false);
      foreach ($subdirs as $subdir) {
        $dir_ids[] = $subdir->get_id();
      }
    } catch (\Exception $e) {
    }
  }



  // overwrite case: untag the files from any of their existing tags that are
  // not given in the array of new tags
  if ($overwrite) {
    if ($tag_count === 0) {
      // special case: untag the files from all existing tags
      $db->query_in('
        DELETE `FILE_TAG_MAP` FROM `FILE_TAG_MAP`
        JOIN `FILES` ON `FILE_TAG_MAP`.`file_id` = `FILES`.`id`
        WHERE `FILES`.`dir_id` IN (%s)
      ', $dir_ids);
    } else {
      $db->query_in("
        DELETE `FILE_TAG_MAP` FROM `FILE_TAG_MAP`
        JOIN `TAGS` ON `FILE_TAG_MAP`.`tag_id` = `TAGS`.`id`
        JOIN `FILES` ON `FILE_TAG_MAP`.`file_id` = `FILES`.`id`
        WHERE `TAGS`.`name` NOT IN (%s)
        AND `FILES`.`dir_id` IN (%s)
      ", array($tag_names, $dir_ids));
    }
  }



  // insert the new tags; existing tags are excluded by ON DUPLICATE KEY UPDATE
  if ($tag_count > 0) {
    $refiler->insert_tags($tag_names);

    // insert into map
    $statement = $db->query_in("
      INSERT INTO `FILE_TAG_MAP` (`file_id`, `tag_id`)
      SELECT `FILES`.`id`, `TAGS`.`id`
      FROM `FILES`, `TAGS`
      WHERE `TAGS`.`name` IN (%s)
      AND `FILES`.`dir_id` IN (%s)
      ON DUPLICATE KEY UPDATE `FILE_TAG_MAP`.`id` = `FILE_TAG_MAP`.`id`
    ", array($tag_names, $dir_ids));
  }



  $db->commit();

  echo json_encode(array(
    'success' => true,
    'tags' => $refiler->get_tag_arrays($tag_names)
  ));
});

?>
