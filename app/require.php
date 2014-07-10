<?php

namespace Refiler;

// config
require __DIR__ . '/config-default.php';
require __DIR__ . '/config.php';
$config['refiler_path'] = __DIR__;
chdir($config['base_path']);

// require all the class scripts manually to avoid doing checks in autoloading
require __DIR__ . '/classes/Auth.php';
require __DIR__ . '/classes/Curl.php';
require __DIR__ . '/classes/DB.php';
require __DIR__ . '/classes/Dir.php';
require __DIR__ . '/classes/File.php';
require __DIR__ . '/classes/Image.php';
require __DIR__ . '/classes/Logger.php';
require __DIR__ . '/classes/Refiler.php';
require __DIR__ . '/classes/Tag.php';



////////////////////////////////////////////////////////////////////// constants

# image has no thumb
define('NO_THUMB', 'none');
# image has no thumb because calling imagecreatefrom...() would use more than
# 25% of memory_limit
define('NO_THUMB_TOO_LARGE', 'large');
# failed to create thumb
define('NO_THUMB_CORRUPT', 'corrupt');
# image is too small and serves as its own thumb
define('SELF_THUMB', 'self');



///////////////////////////////////////////////////////////////// error handling

set_error_handler('Refiler\error_handler', E_ALL);

function error_handler($level, $message, $file, $line) {
  global $config, $auth;

  // print the error to admins if the config option is set
  if (true || $auth && $auth->has_permission('admin')
      && isset_or($config['show_errors_to_admins'])) {
    switch ($level) {
    case E_ERROR:
      $level = 'E_ERROR';
      break;
    case E_WARNING:
      // suppress thumb errors; see Refiler\Image
      if (preg_match('/^(exif_thumbnail|imagecreatefrom|libpng)/', $message)) {
        echo "Warning: $message\n";
        return;
      }
      $level = 'E_WARNING';
      break;
    case E_NOTICE:
      $level = 'E_NOTICE';
      break;
    case E_STRICT:
      $level = 'E_STRICT';
      break;
    case E_DEPRECATED:
      $level = 'E_DEPRECATED';
      break;
    }
    echo "<pre>\n";
    echo "Error\n";
    echo "Level: $level\n";
    echo "Message: $message\n";
    echo "File: $file\n";
    echo "Line: $line\n";
    echo "Backtrace:\n";
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    echo substr(print_r($backtrace, true), 0, 100000);
    echo "</pre>\n";
  } else {
    echo 'An error has occurred.';
  }

  exit;
}

register_shutdown_function(function () {
  global $config, $logger;

  // last error
  $error = error_get_last();
  if ($error !== null && $error['type'] !== E_NOTICE) {
    error_handler($error['type'], $error['message'], $error['file'],
      $error['line']);
  }

  // logger footer
  if ($logger && !preg_match('/(\/get\/|\/post\/)/', $_SERVER['SCRIPT_NAME'])) {
    echo $logger->footer();
  }
});



//////////////////////////////////////////////////////////////// basic functions

/**
 * Shortcut for isset() with a default value.
 * @param  mixed      $var
 * @param  mixed|null $or
 * @return mixed      $var if it is set; $or otherwise.
 */
function isset_or(&$var, $or = null) {
  return isset($var) ? $var : $or;
}



///////////////////////////////////////////////////////////////// file functions

/**
 * Write to the given file.
 * @param string $path
 * @param string $text
 */
function write($path, $text) {
  $file = fopen($path, 'wb');
  fwrite($file, $text);
  fclose($file);
}

/**
 * Append to the given file.
 * @param string $path
 * @param string $text
 */
function append($path, $text) {
  $file = fopen($path, 'a+b');
  fwrite($file, $text);
  fclose($file);
}

/**
 * Delete the given dir and all its files recursively.
 * @param  string  $dir
 * @return boolean Success.
 */
function recursive_rmdir($dir) {
  $handle = opendir($dir);
  while ($file = readdir($handle)) {
    if (is_file("$dir/$file")) {
      unlink("$dir/$file");
    } elseif (is_dir("$dir/$file") && $file != '.' && $file != '..') {
      recursive_rmdir("$dir/$file");
    }
  }
  closedir($handle);
  return rmdir($dir);
}



/////////////////////////////////////////////////////////////// string functions

/**
 * @param  string  $string
 * @param  string  $char
 * @param  boolean $return_string Whether to return $string if $char isn't
 *   found.
 * @param  boolean $include_char  Whether to include $char.
 * @return string  The substring of $string after the last occurrence of
 *   $char.
 */
function after($string, $char, $return_string = true, $include_char = false) {
  if (strrpos($string, $char) !== false) {
    return substr($string, strrpos($string, $char) + ($include_char ? 0 : 1));
  } else {
    return $return_string ? $string : '';
  }
}

/**
 * @param  string  $string
 * @param  string  $char
 * @param  boolean $return_string Whether to return $string if $char isn't
 *   found.
 * @return string  The substring of $string before the first occurrence of
 *   $char.
 */
function before($string, $char, $return_string = true) {
  if (strpos($string, $char) !== false) {
    return substr($string, 0, strpos($string, $char));
  } else {
    return $return_string ? $string : '';
  }
}

/**
 * @param  string  $string
 * @param  string  $char
 * @param  boolean $return_string Whether to return $string if $char isn't
 *   found.
 * @param  boolean $include_char  Whether to include $char.
 * @return string  The substring of $string before the last occurrence of
 *   $char.
 */
function before_last($string, $char, $return_string = true,
    $include_char = false) {
  if (strpos($string, $char) !== false) {
    return substr(
      $string,
      0,
      strrpos($string, $char) + ($include_char ? 1 : 0)
    );
  } else {
    return $return_string ? $string : '';
  }
}



//////////////////////////////////////////////////////////////// array functions

/**
 * @return boolean Whether the array is multi-dimensional.
 */
function is_multi_array($array) {
  return is_array($array) && (count($array) !== count($array, COUNT_RECURSIVE));
}

/**
 * @param  array  $array
 * @param  string $key
 * @return array  Array containing the nested elements with the given key.
 */
function multi_array_values($array, $key) {
  $return = array();
  foreach ($array as $a) {
    $return[] = $a[$key];
  }
  return $return;
}

?>