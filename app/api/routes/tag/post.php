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
  $relative_names = array_merge($parent_names, $child_names);

  if (count($relative_names) === 0) { // special case: delete all relatives
    $tag->update_relatives(array(), array());
  } else {
    // insert tags; existing tags are excluded
    $refiler->insert_tags($relative_names);

    // get the tag ids
    $relative_rows = $refiler->get_tag_rows($relative_names);

    // filter the parent and child ids
    $parent_ids = array();
    $child_ids = array();
    foreach ($relative_rows as $relative_row) {
      if (in_array($relative_row['name'], $parent_names)) {
        $parent_ids[] = $relative_row['id'];
      }
      if (in_array($relative_row['name'], $child_names)) {
        $child_ids[] = $relative_row['id'];
      }
    }

    // update relatives
    $tag->update_relatives($parent_ids, $child_ids);
  }

  // end
  $db->commit();

  echo json_encode(array(
    'success' => true,
    'tag' => $tag->get_array(), // TODO: set relatives without the extra query
    'tags' => $refiler->get_tag_arrays($relative_names)
  ));
});

?>
