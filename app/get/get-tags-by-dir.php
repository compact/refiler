<?php

/**
 * TODO not used, not really necessary
 * Return the tag names shared by all files in the given dir.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// GET params
$dir_path = isset_or($_GET['dir']);

if (empty($dir_path)) {
  echo json_encode(array(
    'success' => false,
    'error' => 'No folder given'
  ));
  exit;
}

// init
$db = new DB($config['db']);
$refiler = new Refiler($config, $db);

$dir = new Dir($refiler, $dir_path);
$rows = $dir->get_common_tag_rows();
$tags = multi_array_values($rows, 'name');
natcasesort($tags);
$tags = implode(', ', $tags);

echo json_encode(array(
  'success' => true,
  'tags' => $tags
));

?>