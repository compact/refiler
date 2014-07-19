<?php

/**
 * Tag the given file with the given tags (by names). If a dir and name are
 *   given (optional), move the file and return the new dir and name (the new
 *   path will differ from the given path if another file already exists
 *   there).
 */

namespace Refiler;

$app->post('/file/:id.json', function ($file_id) use ($app, $config) {
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
  $caption = $app->request->post('caption');
  $dir_path = $app->request->post('dirPath'); // optional
  $file_name = $app->request->post('name'); // optional

  // sanitize
  $file_id = (int)$file_id;
  $tag_names = Refiler::sanitize_tag_names($tag_names);
  $caption = File::sanitize_caption($caption);
  if ($dir_path !== null) {
    $dir_path = Dir::sanitize_path($dir_path);
    $file_name = File::sanitize_name($dir_path, $file_name);
  }

  // init
  $refiler = new Refiler($config, $db);

  // get the file
  try {
    $file = new File($refiler, $file_id);
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
    exit;
  }

  // params in the UPDATE query
  $params = array();

  // set the new caption if it is different
  if ($file->get_caption() !== $caption) {
    $file->set_caption($caption); // does not update the file row
    $params[':caption'] = $caption;
  }

  // used below
  $old_file_type = $file->get_type();

  // move the file if the new path is different
  if ($dir_path !== null &&
      $file->get_path() !== File::path($dir_path, $file_name)) {
    $old_dir_path = $file->get_dir()->get_path();
    $old_file_name = $file->get_name();

    // move the file
    try {
      $file->move($dir_path, $file_name, false); // don't update the file row
    } catch (\Exception $e) {
      echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
      ));
      exit;
    }

    // bind params
    if ($old_dir_path !== $dir_path) {
      $params[':dir_id'] = $file->get_dir()->get_id();
    }
    if ($old_file_name !== $file_name) {
      $params[':name'] = $file_name;
    }
  }

  // set the new type if it is different
  $file_type = $file->get_type();
  if ($old_file_type !== $file_type) {
    $params[':type'] = $file_type;
  }

  // update the file row
  if (count($params) > 0) {
    // build the SET clause
    $set = array();
    foreach ($params as $key => $value) {
      $column = substr($key, 1);
      $set[] = "`$column` = :$column";
    }
    $set = implode(',', $set);

    // bind the file id last since it's not part of SET
    $params[':id'] = $file_id;

    // update the file row
    $db->query("
      UPDATE `FILES`
      SET $set
      WHERE `id` = :id
      LIMIT 1
    ", $params);
  }



  // tag the file
  if (count($tag_names) === 0) {
    // special case: no tags, so delete all tags
    $db->query('DELETE FROM `FILE_TAG_MAP` WHERE `file_id` = ?', array($file_id));
  } else {
    $db->begin_transaction();

    // insert tags; existing tags are excluded by ON DUPLICATE KEY UPDATE
    $refiler->insert_tags($tag_names);

    // get the tags
    $tag_rows = $refiler->get_tag_rows($tag_names);

    // delete existing tags not in the list of new tags
    $db->query_in('DELETE FROM `FILE_TAG_MAP`
      WHERE `tag_id` NOT IN (%s)
      AND `file_id` = ?', multi_array_values($tag_rows, 'id'), array($file_id));

    // insert into map; existing pairs are excluded
    $rows = array();
    foreach ($tag_rows as $tag_row) {
      $rows[] = array(
        'file_id' => $file_id,
        'tag_id' => $tag_row['id']
      );
    }
    $db->insert('FILE_TAG_MAP', $rows, true);

    $db->commit();
  }



  // output the result
  echo json_encode(array(
    'success' => true,
    'file' => $file->get_array(),
    'tags' => $refiler->get_tag_arrays($tag_rows)
  ));
});

?>
