<?php

/**
 * Return all dirs.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
$db = new DB($config['db']);
$refiler = new Refiler($config, $db);

// get the dirs
$rows = $refiler->get_dir_rows(); // with file counts

echo json_encode(array(
  'success' => true,
  'dirs' => $rows
));

?>