<?php

/**
 * Create thumbs for images that don't already have them, according to the
 *   database. This means even if a thumb file exists at the correct path, a
 *   new thumb will still be created if the database doesn't know about it.
 */

namespace Refiler;

$app->post('/thumbs.json', function () use ($config) {
  $db = new DB($config['db']);

  // auth
  $auth = new Auth($config['auth'], $db);
  if (!$auth->has_permission('admin')) {
    exit('Forbidden');
  }

  // arbitrary constant to limits the number of thumbless images to be found
  $limit = 10000;

  // init
  $refiler = new Refiler($config, $db);

  // find images with no thumbs
  $rows = $db->fetch_all("
    SELECT
      `FILES`.*,
      `DIRS`.`path` AS `dir_path`
    FROM `FILES` LEFT JOIN `DIRS`
    ON `FILES`.`dir_id` = `DIRS`.`id`
    WHERE `type` IN ('jpg', 'jpeg', 'png', 'gif')
    AND `thumb_type` = ?
    LIMIT $limit
  ", array(NO_THUMB)); // can't bind LIMIT value

  $ids_by_thumb_type = array();
  $output = count($rows) . " files selected\n";
  $output .= "dimensions path ~ thumb type\n\n";

  // debug
  // exit(print_r($rows));

  foreach ($rows as $row) {
    $properties = $row;

    // TODO: better to cache these to avoid repeatedly constructing the same Dir
    $properties['dir'] = new Dir($refiler, array(
      'id' => $row['dir_id'],
      'path' => $row['dir_path']
    )); 

    // construct the image
    $image = File::new_file_or_image($refiler, $properties);

    if ($image->is_image()) {
      $output .= "{$image->get_width()} x {$image->get_height()}";
      $output .= " ~ {$image->get_path()} ~ ";

      // attempt to create a thumb
      $thumb_type = $image->create_thumb();

      if ($thumb_type !== NO_THUMB) {
        $ids_by_thumb_type[$thumb_type][] = $image->get_id();
      }
      $output .= "$thumb_type\n";
    }
  }

  // update the rows
  foreach ($ids_by_thumb_type as $thumb_type => $ids) {
    if (count($ids) > 0) {
      $statement = $db->query_in("UPDATE `FILES`
        SET `thumb_type` = '$thumb_type'
        WHERE `id` IN (%s)", $ids);
      $output .= "{$statement->rowCount()} rows updated for type $thumb_type\n";
    }
  }

  // output
  echo json_encode(array(
    'success' => true,
    'output' => str_replace("\n", '<br>', $output)
  ));
});

?>
