<?php

/**
 * Synchronize the dirs table based on existing dirs. A use case is when dirs
 *   get deleted or renamed manually without the use of Refiler.
 */

namespace Refiler;
require '../require.php';

$db = new DB($config['db']);

// auth
$auth = new Auth($config['auth'], $db);
if (!$auth->has_permission('admin')) {
  exit('Forbidden');
}

// init
$logger = new Logger();
$logger->set_content_type('text/plain');
$refiler = new Refiler($config, $db, $logger);



// GET params
$dir_path = isset_or($_GET['path'], '.'); // sanitized in the Dir constructor



// get subdir paths that actually exist
$dir = new Dir($refiler, $dir_path);
$paths_in_fs = $dir->get_subdir_paths_in_filesystem();

// get subdirs as stored in the db
$subdir_rows = $dir->get_subdir_rows(true, true);



// insert rows for any dirs that aren't already stored in the db
$rows_to_insert = array_map(function ($path) {
  return array(
    'path' => $path
  );
}, $paths_in_fs);
$statement = $db->insert('DIRS', $rows_to_insert, true);
echo "rows inserted: {$statement->rowCount()}\n";



// determine the rows for dirs that don't exist anymore, and delete them
$rows_to_delete = array_filter($subdir_rows,
  function ($subdir_row) use ($paths_in_fs) {
    return !in_array($subdir_row['path'], $paths_in_fs);
  }
);
if (count($rows_to_delete) > 0) {
  $statement = $db->query_in(
    'DELETE FROM `DIRS` WHERE `id` IN (%s)',
    multi_array_values($rows_to_delete, 'id')
  );
  echo "rows deleted: {$statement->rowCount()}\n";
  echo print_r($rows_to_delete, true) . "\n";
}

?>