<?php

/**
 * Delete all thumbnails.
 */

namespace Refiler;

$app->delete('/thumbs.json', function () use ($config) {
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

  // delete thumbs
  echo shell_exec("find thumbs -name 'thumb-*' -print0 | xargs -0 rm -rv");

  // reset the `thumb_type` column
  $statement = $db->query('
    UPDATE `FILES`
    SET `thumb_type` = ?
  ', array(NO_THUMB));
  echo "{$statement->rowCount()} rows deleted\n";
});

?>
