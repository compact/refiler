<?php

/**
 * Return all dirs.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
$db = new DB($config['db']);

// auth
$auth = new Auth($config['auth'], $db);
if (!$auth->has_permission('view')) {
  echo json_encode(array(
    'success' => false,
    'error' => 'Forbidden'
  ));
  exit;
}

// get the dirs
$rows = $db->fetch_all('
  SELECT
    `DIRS`.*,
    COUNT(`FILES`.`id`) AS `fileCount`
  FROM `DIRS` LEFT JOIN `FILES`
  ON `DIRS`.`id` = `FILES`.`dir_id`
  GROUP BY `DIRS`.`id`
  ORDER BY `DIRS`.`path`
');

// typecasting
$arrays = array_map(function ($row) {
  return array(
    'id' => (int)$row['id'],
    'path' => $row['path'],
    'fileCount' => (int)$row['fileCount']
  );
}, $rows);

echo json_encode(array(
  'success' => true,
  'dirs' => $arrays
));

?>