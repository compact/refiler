<?php

/**
 * Output the current user's logged-in-ness and permissions, and the tags and
 *   dirs they have permission to view.
 */

namespace Refiler;
require '../require.php';
header('Content-type: application/json');

// init
$db = new DB($config['db']);
$auth = new Auth($config['auth'], $db);



// build the user array to be output
$user_array = array(
  'loggedIn' => $auth->logged_in(),
  'permissions' => $auth->get_permissions()
);



// get the tags
$tag_arrays = array();
if ($auth->has_permission('view')) {
  $tag_rows = $db->fetch_all("
    SELECT
      `TAGS`.*,
      COUNT(DISTINCT `FILE_TAG_MAP`.`id`) AS `fileCount`,
      COUNT(DISTINCT `parents`.`id`) AS `parentCount`,
      COUNT(DISTINCT `children`.`id`) AS `childCount`
    FROM `TAGS`
    LEFT JOIN `FILE_TAG_MAP` ON `TAGS`.`id` = `FILE_TAG_MAP`.`tag_id`
    LEFT JOIN `TAG_MAP` AS `parents` ON `TAGS`.`id` = `parents`.`child_id`
    LEFT JOIN `TAG_MAP` AS `children` ON `TAGS`.`id` = `children`.`parent_id`
    GROUP BY `TAGS`.`id`
    ORDER BY `TAGS`.`name`
  ");

  // typecasting
  $tag_arrays = array_map(function ($row) {
    return array(
      'id' => (int)$row['id'],
      'name' => $row['name'],
      'url' => $row['url'],
      'caption' => $row['caption'],
      'fileCount' => (int)$row['fileCount'],
      'parentCount' => (int)$row['parentCount'],
      'childCount' => (int)$row['childCount']
    );
  }, $tag_rows);
}



// get the dirs
$dir_arrays = array();
if ($auth->has_permission('view')) {
  $dir_rows = $db->fetch_all('
    SELECT
      `DIRS`.*,
      COUNT(`FILES`.`id`) AS `fileCount`
    FROM `DIRS` LEFT JOIN `FILES`
    ON `DIRS`.`id` = `FILES`.`dir_id`
    GROUP BY `DIRS`.`id`
    ORDER BY `DIRS`.`path`
  ');

  // typecasting
  $dir_arrays = array_map(function ($row) {
    return array(
      'id' => (int)$row['id'],
      'path' => $row['path'],
      'fileCount' => (int)$row['fileCount']
    );
  }, $dir_rows);
}



// output the combined result
echo json_encode(array(
  'success' => true,
  'user' => $user_array,
  'tags' => $tag_arrays,
  'dirs' => $dir_arrays
));

?>