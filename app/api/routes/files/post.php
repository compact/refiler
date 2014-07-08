<?php

/**
 * Move the given files to the given dir.
 */

namespace Refiler;

$app->post('/files/:ids.json', function ($file_ids) use ($app, $config) {
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
  $dir_id = $app->request->post('dirId');

  // sanitize
  $file_ids = array_map('intval', explode(',', $file_ids));
  $dir_id = (int)$dir_id;

  if (empty($file_ids) || empty($dir_id)) {
    echo json_encode(array(
      'success' => false,
      'error' => 'No files or folder selected.'
    ));
    exit;
  }

  // init
  $refiler = new Refiler($config, $db);
  $dir = new Dir($refiler, $dir_id);

  foreach ($file_ids as $file_id) {
    try {
      $file = new File($refiler, $file_id);

      // move the file; don't update the row here (third argument), update all
      // rows with one query below
      $file->move($dir->get_path(), $file->get_name(), false);
    } catch (\Exception $e) {
      echo json_encode(array(
        'success' => false,
        'error' => $e->getMessage()
      ));
      exit;
    }
  }

  $count = count($file_ids);
  $db->query_in("
    UPDATE `FILES`
    SET `dir_id` = $dir_id
    WHERE `id` IN (%s)
    LIMIT $count
  ", $file_ids);

  echo json_encode(array(
    'success' => true
  ));
});

?>
