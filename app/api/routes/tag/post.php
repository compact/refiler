<?php

/**
 * Edit the given tag's name, parent tags, and child tags.
 */

namespace Refiler;

$app->post('/tag/:id.json', function ($id) use ($app, $config) {
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
  $name = $app->request->post('name');
  $url = $app->request->post('url');
  $caption = $app->request->post('caption');
  $parent_names = $app->request->post('parentNames');
  $child_names = $app->request->post('childNames');

  // sanitize
  $id = (int)$id;
  $name = Tag::sanitize_name($name);
  $url = Tag::sanitize_url($url);
  $caption = (string)$caption;
  $parent_names = Refiler::sanitize_tag_names($parent_names);
  $child_names = Refiler::sanitize_tag_names($child_names);

  if (empty($id) || empty($name)) {
    echo json_encode(array(
      'success' => false,
      'error' => 'No tag given'
    ));
    exit;
  }

  // init
  $refiler = new Refiler($config, $db);
  $db->begin_transaction();

  try {
    // get the tag
    $tag = new Tag($refiler, $id);

    // update the tag
    $tag->update($name, $url, $caption);
  } catch (\Exception $e) {
    echo json_encode(array(
      'success' => false,
      'error' => $e->getMessage()
    ));
    exit;
  }

  // no array_unique(); logically there should be no duplicates, though they are
  // permitted
  $all_names = array_merge($parent_names, $child_names);
  $count = count($all_names);

  if ($count === 0) { // special case: delete all relatives
    $tag->update_relatives(array(), array());
  } else {
    // insert tags; existing tags are excluded
    $refiler->insert_tags($all_names);

    // get the tag ids
    $relatives = $db->fetch_all_in("SELECT `id`, `name` FROM `TAGS`
      WHERE `name` IN (%s)
      LIMIT $count", $all_names);

    // filter the parent and child ids
    $parent_ids = array();
    $child_ids = array();
    foreach ($relatives as $relative) {
      if (in_array($relative['name'], $parent_names)) {
        $parent_ids[] = $relative['id'];
      }
      if (in_array($relative['name'], $child_names)) {
        $child_ids[] = $relative['id'];
      }
    }

    // update relatives
    $tag->update_relatives($parent_ids, $child_ids);
  }

  // end
  $db->commit();

  echo json_encode(array(
    'success' => true,
    'tag' => $tag->get_array()
  ));
});

?>