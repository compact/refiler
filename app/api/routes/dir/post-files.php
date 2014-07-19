<?php

/**
 * Upload the given files to the given dir, tagging them with optionally given
 *   tags.
 * TODO write Curl_Multi class
 */

namespace Refiler;

$app->post('/dir/:id/files.json', function ($dir_id) use ($app, $config) {
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
  $urls = $app->request->post('urls'); // string

  // sanitize; the other data is sanitized below
  $dir_id = (int)$dir_id;

  // init
  $refiler = new Refiler($config, $db);

  // parse dir
  $dir = new Dir($refiler, $dir_id);
  $dir_path = $dir->get_path();

  // parse urls
  $urls = preg_split('/\n|\r/', $urls, -1, PREG_SPLIT_NO_EMPTY);
  $urls = array_map('trim', $urls);

  if (empty($dir_path) || count($urls) === 0) {
    echo json_encode(array(
      'success' => false,
      'error' => 'No files given'
    ));
    exit;
  }

  // init curl
  $curl = new Curl();
  /* TODO progress
  $curl->set(CURLOPT_NOPROGRESS, false);
  $curl->set(CURLOPT_PROGRESSFUNCTION, function ($download_total, $download_progress) {
    echo("$dl_progress / $dl_total\n");
  });
  */

  // each url will have a corresponding element in one of these two arrays
  $uploaded_file_rows = array(); // rows to insert
  $failed_urls = array(); // urls that failed to be saved by curl

  // curl the files
  foreach ($urls as $url) {
    // sanitize the path
    $name = after($url, '/');
    $name = File::sanitize_name($dir_path, $name);
    $path = "$dir_path/$name";

    // curl
    $success = $curl->save($url, $path);

    if ($success) {
      chmod($path, 0644);

      // construct the file
      $file = File::new_file_or_image($refiler, array(
        'dir' => $dir,
        'name' => $name
      ));

      // create a thumb if the file is an image
      if ($file->is_image()) {
        $file->create_thumb();
      }

      $uploaded_files[] = $file;
    } else {
      $failed_urls[] = $url;
    }
  }

  // insert rows
  $uploaded_file_count = count($uploaded_files);

  if ($uploaded_file_count > 0) {
    $db = $refiler->get_db();
    $db->begin_transaction();

    // insert the files
    $db->insert('FILES', array_map(function ($file) {
      return $file->generate_row();
    }, $uploaded_files));

    // this is the first id of the inserted rows, not the last
    $last_insert_id = $db->get_last_insert_id();

    // the inserted row ids are guaranteed sequential for innodb_autoinc_lock_mode
    // = 0 or 1, so we don't have to worry about determining the ids in a more
    // complicated manner than this
    $uploaded_file_ids = range(
      $last_insert_id,
      $last_insert_id + $uploaded_file_count - 1
    );

    // sanitize
    $tag_names = Refiler::sanitize_tag_names($tag_names);

    // insert any new tags
    if (count($tag_names) > 0) {
      $refiler->insert_tags($tag_names);
      $db->query_in("
        INSERT INTO `FILE_TAG_MAP` (`file_id`, `tag_id`)
        SELECT `FILES`.`id`, `TAGS`.`id`
        FROM `FILES`, `TAGS`
        WHERE `TAGS`.`name` IN (%s)
        AND `FILES`.`id` IN (%s)
        ON DUPLICATE KEY UPDATE `FILE_TAG_MAP`.`id` = `FILE_TAG_MAP`.`id`
      ", array($tag_names, $uploaded_file_ids));
    }

    $db->commit();

    // set the file ids for output below
    foreach ($uploaded_files as $key => $file) {
      $file->set_id($uploaded_file_ids[$key]);
    }
  }

  // output the result
  echo json_encode(array(
    'success' => true,
    'files' => array_map(function ($file) {
      return $file->get_array();
    }, $uploaded_files),
    'failedUrls' => $failed_urls,
    'tags' => $refiler->get_tag_arrays($tag_names)
  ));
});

?>
