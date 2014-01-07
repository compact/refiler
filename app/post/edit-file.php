<?php

/**
 * Tag the given file (by id) with the given tags (by names).
 * If a dir and name are given (optional), move the file and return the new dir
 * and name (the new path will differ from the given path if another file
 * already exists there).
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// POST data
$file_id = isset_or($_POST['id']);
$tag_names = isset_or($_POST['tagNames']);
$caption = isset_or($_POST['caption']);
$dir_path = isset_or($_POST['dirPath']); // optional
$file_name = isset_or($_POST['name']); // optional

// sanitize
$file_id = (int)$file_id;
$tag_names = Refiler::sanitize_tag_names($tag_names);
$caption = File::sanitize_caption($caption);
if (!empty($dir_path)) {
  $dir_path = Dir::sanitize_path($dir_path);
  $file_name = File::sanitize_name($dir_path, $file_name);
}

if (empty($file_id)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No file given'
  ));
  exit;
}

// init
$db = new DB($config['db']);
$refiler = new Refiler($config, $db);

// this array is output at the end as JSON
$result = array('success' => true);



// move the file
if (!empty($dir_path)) {
  $file = new File($refiler, $file_id);
  try {
    $file->move($dir_path, $file_name);
    $result['dir'] = $dir_path;
    $result['name'] = $file_name;
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
    exit;
  }
}



// update the caption
// TODO merge this query with $file->move() above
$db->query('UPDATE `FILES`
  SET `caption` = ?
  WHERE `id` = ?
  LIMIT 1', array($caption, $file_id));
$result['caption'] = $caption;



// tag the file
$tag_count = count($tag_names);
$result['tags'] = array();
if ($tag_count === 0) {
  // special case: no tags, so delete all tags
  $db->query('DELETE FROM `FILE_TAG_MAP` WHERE `file_id` = ?', array($file_id));
} else {
  $db->begin_transaction();

  // insert tags; existing tags are excluded by ON DUPLICATE KEY UPDATE
  $refiler->insert_tags($tag_names);

  // get the tags
  $tags = $db->fetch_all_in("SELECT `id`, `url`, `name` FROM `TAGS`
    WHERE `name` IN (%s)
    LIMIT $tag_count", $tag_names);

  // delete existing tags not in the list of new tags
  $db->query_in('DELETE FROM `FILE_TAG_MAP`
    WHERE `tag_id` NOT IN (%s)
    AND `file_id` = ?', multi_array_values($tags, 'id'), array($file_id));

  // insert into map; existing pairs are excluded
  $rows = array();
  foreach ($tags as $tag) {
    $rows[] = array(
      'file_id' => $file_id,
      'tag_id' => $tag['id']
    );
    $result['tags'][] = array(
      'url' => $tag['url'],
      'name' => $tag['name']
    );
  }
  $db->insert('FILE_TAG_MAP', $rows, true);

  $db->commit();
}



// output the result
echo json_encode($result);

?>