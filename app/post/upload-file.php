<?php

/**
 * Move the given uploaded file to the given path (by filename and dir id),
 *   tagging it with optionally given tags.
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
$name = isset_or($_POST['name']);
$dir_id = isset_or($_POST['dirId']);
$tag_names = isset_or($_POST['tagNames']);
$file = isset_or($_FILES['file']);

// sanitize
$dir_id = (int)$dir_id;
// $_POST['tagNames'] is a JSON string returned from JSON.stringify(), which is
// a special case, unlike every other script, due to $fileUpload not using $http
$tag_names = json_decode($tag_names);

// check uploaded file
if (!is_uploaded_file($file['tmp_name'])) {
  switch ($file['error']) {
  case UPLOAD_ERR_INI_SIZE:
    $message = 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
    break;
  case UPLOAD_ERR_FORM_SIZE:
    $message = 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.';
    break;
  case UPLOAD_ERR_PARTIAL:
    $message = 'The uploaded file was only partially uploaded.';
    break;
  case UPLOAD_ERR_NO_FILE:
    $message = 'No file was uploaded.';
    break;
  case UPLOAD_ERR_NO_TMP_DIR:
    $message = 'Missing a temporary folder.';
    break;
  case UPLOAD_ERR_CANT_WRITE:
    $message = 'Failed to write file to disk';
    break;
  case UPLOAD_ERR_EXTENSION:
    $message = 'A PHP extension stopped the file upload.';
    break;
  default:
    $message = 'Unknown upload error.';
    break;
  }

  // output the error
  echo json_encode(array(
    'success' => false,
    'error' => $message,
    'file' => array(
      'name' => $name
    )
  ));
  exit;
}



// init
$refiler = new Refiler($config, $db);

// move the file
try {
  $dir = new Dir($refiler, $dir_id);
} catch (\Exception $e) {
  echo json_encode(array(
    'success' => false,
    'error' => $e->getMessage()
  ));
  exit;
}
$dir_path = $dir->get_path();
$name = File::sanitize_name($dir_path, $name);
$path = "$dir_path/$name";
rename($file['tmp_name'], $path);
chmod($path, 0644);

$db->begin_transaction();

// insert a row for the file
$file = File::new_file_or_image($refiler, array(
  'dir' => $dir,
  'name' => $name
));
if ($file->is_image()) {
  $file->create_thumb();
}
$file->insert();

// insert tags for the file
$tag_names = Refiler::sanitize_tag_names($tag_names);
if (count($tag_names) > 0) {
  $id = $db->get_last_insert_id();
  $refiler->insert_tags($tag_names);
  $db->query_in("
    INSERT INTO `FILE_TAG_MAP` (`file_id`, `tag_id`)
    SELECT $id, `TAGS`.`id`
    FROM `TAGS`
    WHERE `TAGS`.`name` IN (%s)
  ", $tag_names);
}

$db->commit();



// output the result
echo json_encode(array(
  'success' => true,
  'file' => $file->get_array()
));

?>
