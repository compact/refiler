<?php

/**
 * Synchronize the dirs table based on existing dirs. A use case is when dirs
 *   get deleted or renamed manually without the use of Refiler.
 */

namespace Refiler;

$app->post('/dir/:id/subdirs.json', function ($id) use ($app, $config) {
  $db = new DB($config['db']);

  // auth
  $auth = new Auth($config['auth'], $db);
  if (!$auth->has_permission('admin')) {
    exit('Forbidden');
  }

  // sanitize
  $id = (int)$id;

  // init
  $refiler = new Refiler($config, $db);



  // get subdir paths that actually exist
  $dir = new Dir($refiler, $id);
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
  $output = "Rows inserted: {$statement->rowCount()}<br>";



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
    $output = "Rows deleted: {$statement->rowCount()}<br>";
    $output = print_r($rows_to_delete, true) . '<br>';
  }



  // output
  echo json_encode(array(
    'success' => true,
    'output' => $output
  ));
});

?>
