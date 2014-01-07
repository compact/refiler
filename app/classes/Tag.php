<?php

namespace Refiler;

/**
 * Each instance of this class is an individual tag as stored in the `TAGS`
 *   table. The tag has to exist.
 */
class Tag {
  // injected dependencies
  protected $db;

  // all fields in the `TAGS` table
  protected $id = 0;
  protected $name = '';
  protected $url = '';
  protected $caption = '';

  /**
   * @param Refiler $refiler
   * @param int|string|array $param Id, url, or row. In the case of id, the
   *   other properties are not initialized.
   */
  public function __construct(Refiler $refiler, $param = '') {
    $this->db = $refiler->get_db();

    if (is_numeric($param)) {
      $this->id = (int)$param;
    } elseif (is_array($param)) {
      $row = $param;
      $this->id = isset_or($row['id'], 0);
      $this->name = $row['name'];
      $this->url = isset_or($row['url'], '');
      $this->caption = isset_or($row['caption'], '');
    } elseif (is_string($param)) {
      $url = $param;
      $rows = $this->db->fetch_all('SELECT * FROM `TAGS`
        WHERE `url` = ?', array($url));
      if (count($rows) === 0) {
        throw new \Exception('Tag not found');
      } else {
        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->url = $row['url'];
        $this->caption = $row['caption'];
      }
    }
  }

  public static function sanitize_name($name) {
    $name = (string)$name;
    $name = trim($name);
    return $name;
  }
  public function get_name() {
    return $this->name;
  }
  public static function sanitize_url($url) {
    $url = (string)$url;
    $url = trim($url);
    $url = strtolower($url);
    $url = preg_replace('/[ \W]+/', '-', $url);
    return $url;
  }
  public function get_url() {
    return $this->url;
  }
  public function get_default_url() {
    $url = strtolower($this->name);
    $url = preg_replace('/[ \W]+/', '-', $url);
    return $url;
  }
  public function get_caption() {
    return $this->caption;
  }

  /**
   * @return array All files tagged with this tag.
   */
  public function get_files() {
    return $this->db->fetch_all('SELECT *
      FROM `FILE_TAG_MAP` JOIN `FILES`
      ON `FILE_TAG_MAP`.`file_id` = `FILES`.`id`
      WHERE `tag_id` = ?', array($this->id));
  }
  /**
   * @return array Array of two multidimensional arrays:
   *   'parents' => arrays containing keys 'url' and 'name'
   *   'children' => arrays containing keys 'url' and 'name'
   */
  public function get_relatives() {
    $rows = $this->db->fetch_all('SELECT `TAGS`.`name`, `TAGS`.`url`,
      `TAG_MAP`.`parent_id`, `TAG_MAP`.`child_id`
      FROM `TAGS` JOIN `TAG_MAP`
      ON `TAGS`.`id` = `TAG_MAP`.`parent_id`
      OR `TAGS`.`id` = `TAG_MAP`.`child_id`
      WHERE (`TAG_MAP`.`parent_id` = :id OR `TAG_MAP`.`child_id` = :id)
      AND `TAGS`.`id` != :id', array(':id' => $this->id));
    $relatives = array(
      'parents' => array(),
      'children' => array()
    );
    foreach ($rows as $row) {
      if ((int)$row['child_id'] === $this->id) {
        $relatives['parents'][] = array(
          'url' => $row['url'],
          'name' => $row['name']
        );
      }
      if ((int)$row['parent_id'] === $this->id) {
        $relatives['children'][] = array(
          'url' => $row['url'],
          'name' => $row['name']
        );
      }
    }
    return $relatives;
  }

  ///////////////////////////////////////////////////////////////// db functions
  public function update($name, $url, $caption) {
    return $this->db->query('UPDATE `TAGS`
      SET `name` = ?, `url` = ?, `caption` = ?
      WHERE `id` = ?', array($name, $url, $caption, $this->id));
  }
  /**
   * Call DB::begin_transaction() and DB::commit() around this function.
   */
  public function update_relatives($parent_ids, $child_ids) {
    // delete existing relatives without the given ids
    $where = array();
    $in_values = array();
    if (count($parent_ids) === 0) {
      $where[] = "`child_id` = {$this->id}";
    } else {
      $where[] = "(`child_id` = {$this->id} AND `parent_id` NOT IN (%s))";
      $in_values[] = $parent_ids;
    }
    if (count($child_ids) === 0) {
      $where[] = "`parent_id` = {$this->id}";
    } else {
      $where[] = "(`parent_id` = {$this->id} AND `child_id` NOT IN (%s))";
      $in_values[] = $child_ids;
    }
    $where = implode(' OR ', $where);
    if (empty($in_values)) {
      $this->db->query("DELETE FROM `TAG_MAP` WHERE $where");
    } else {
      $this->db->query_in("DELETE FROM `TAG_MAP` WHERE $where", $in_values);
    }

    // insert relatives with the given ids (excluding duplicates)
    $rows = array();
    foreach ($parent_ids as $parent_id) {
      $rows[] = array(
        'parent_id' => $parent_id,
        'child_id' => $this->id
      );
    }
    foreach ($child_ids as $child_id) {
      $rows[] = array(
        'parent_id' => $this->id,
        'child_id' => $child_id
      );
    }
    $this->db->insert('TAG_MAP', $rows, true);
  }
  public function delete() {
    return $this->db->query('DELETE FROM `TAGS` WHERE id = ?',
      array($this->id));
  }

  //////////////////////////////////////////////////////////// compare functions
  public static function compare_rows($row1, $row2) {
    return strcmp($row1['name'], $row2['name']);
  }
}

?>