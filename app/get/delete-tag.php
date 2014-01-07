<?php

/**
 * For the given tag, edit its name, parent tags, and child tags.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// GET params
$id = isset_or($_GET['id']);

// sanitize
$id = (int)$id;

if (empty($id)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No tag specified'
  ));
  exit;
}

// init
$db = new DB($config['db']);
$refiler = new Refiler($config, $db);

$tag = new Tag($refiler, $id);

// delete the tag
$tag->delete();

echo json_encode(array(
  'success' => true
));

?>