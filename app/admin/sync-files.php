<?php

/**
 * Synchronize the files table based on existing files. A use case is when files
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

// GET params
$dir_path = isset_or($_GET['dirPath'], '.');

// init
$logger = new Logger();
$logger->set_content_type('text/plain');
$refiler = new Refiler($config, $db, $logger);

// get the dir
$dir = new Dir($refiler, $dir_path);
if (!$dir->exists_in_filesystem()) {
  exit('Dir not found.');
}

// update the dir
$results = $dir->update(array(
  'recursive' => false,
  'deep_update' => true,
  'create_thumbs' => false,
  'file_limit' => -1,
  'query_limit' => -1,
  'log' => true
));

?>