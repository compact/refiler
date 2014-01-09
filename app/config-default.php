<?php

/**
 * The Refiler configuration in PHP is stored in the global variable $config.
 *   Overwrite any of the options in config.php.
 *
 * $config['db'] contains configuration for the class DB.
 *
 * $config['auth'] contains configuration for the class Auth.
 *
 * $config['file'] contains configuration for the class File and its child
 *   Image.
 */

# base path of all files without trailing slash; can be different from __DIR__
$config['base_path'] = __DIR__;

# whether to output error messages to admins
$config['show_errors_to_admins'] = false;

# database credentials
$config['db']['host'] = 'localhost';
$config['db']['port'] = '3306';
$config['db']['name'] = '';
$config['db']['user'] = '';
$config['db']['pass'] = '';

# database table prefixes
$config['db']['table_prefix'] = 'refiler_';
$config['auth']['sentry_table_prefix'] = '';

# guest permissions passed to Refiler\User; must match the values in
# AuthProvider
$config['auth']['default_permissions'] = array(
  'view' => false,
  'edit' => false,
  'admin' => false
);

# extensions of files to be considered as images
$config['file']['image_extensions'] = array(
  'gif', 'jpg', 'jpeg', 'png', 'bmp', 'tiff', 'ico'
);

# maximum thumbnail dimensions
$config['file']['max_thumb_width'] = 125;
$config['file']['max_thumb_height'] = 100;

# the quality at which to save JPEGs
# currently, this affects thumbs only
# the JPEG quality's effect on execution time is negligible; the primary concern
# is filesize
$config['file']['jpeg_quality'] = 83;

# PCRE patterns of directories and files to exclude from being stored in the db
# or viewed by users; paths are relative to $config['base_path']; do not
# include '/' delimiters, as these patterns are joined by '|' into one big
# pattern
$config['exclude_paths'] = array();

?>