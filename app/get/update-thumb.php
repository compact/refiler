<?php

/**
 * Generate a thumbnail for the given file.
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

// GET params
$id = isset_or($_GET['id']);

// sanitize
$id = (int)$id;

if (empty($id)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No file given.'
  ));
  exit;
}

// init
$refiler = new Refiler($config, $db);

// get the file
try {
  $image = new Image($refiler, $id, null);
} catch (\Exception $e) {
  echo json_encode(array(
    'success' => false,
    'error' => $e->getMessage()
  ));
  exit;
}

// check that the file is an image
if (!$image->is_image()) {
  echo json_encode(array(
    'success' => false,
    'error' => 'This file is not an image'
  ));
  exit;
}

$old_thumb_type = $image->get_thumb_type();

// update thumb
$new_thumb_type = $image->create_thumb();

// update file row
if ($old_thumb_type !== $new_thumb_type) {
  $image->update();
}

// output the result
echo json_encode(array(
  'success' => true,
  'file' => $image->get_array()
));

?>
