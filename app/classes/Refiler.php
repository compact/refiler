<?php

namespace Refiler;

/**
 * There should be one instance of this class per script. It gets injected
 *   into the other class constructors, passing along $config and/or $db.
 */
class Refiler {
  // injected dependencies
  protected $config; // array from config.php
  protected $db; // DB object
  protected $logger = null; // Logger object, optional

  /**
   * @param array  $config
   * @param DB     $db
   * @param Logger $logger
   */
  public function __construct(array $config, DB $db, Logger $logger = null) {
    $this->config = $config;
    $this->db = $db;
    $this->logger = $logger;
  }

  /**
   * @param  string $type Optional key of $this->config to return the array at
   *   that key only.
   * @return array
   */
  public function get_config($type = '') {
    return isset($this->config[$type])
      ? $this->config[$type]
      : $this->config;
  }

  public function get_db() {
    return $this->db;
  }

  public function get_logger() {
    if ($this->logger === null) { // anti-pattern
      return new Logger();
    }
    return $this->logger;
  }



  /**
   * @return string Whether the given path is excluded from being stored by
   *   Refiler.
   */
  public function path_is_excluded($path) {
    $subpatterns = $this->config['exclude_paths'];
    $subpatterns[] = '^thumbs$';
    $subpatterns[] = '^thumbs\/';
    return preg_match('/' . implode('|', $subpatterns) . '/', $path);
  }



  /**
   * @param array $names Tag names. Keys do not matter.
   * @return array
   */
  public static function sanitize_tag_names($names) {
    $names = (array)$names;
    $return = array();
    foreach ($names as $name) {
      $name = Tag::sanitize_name($name);
      if (!empty($name)) {
        $return[] = $name;
      }
    }
    return $return;
  }

  /**
   * @param array $rows The only required field is `name`.
   */
  public function insert_tags($names) {
    $rows = array();
    foreach ($names as $name) {
      $tag = new Tag($this, array('name' => $name));
      $url = $tag->get_default_url();
      $rows[] = array(
        'name' => $name,
        'url' => $url
      );
    }
    return $this->db->insert('TAGS', $rows, true);
  }

  /**
   * @param  array $names Tag names.
   * @return array Tag rows.
   */
  public function get_tag_rows($names) {
    return $this->db->fetch_all_in('SELECT * FROM `TAGS`
      WHERE `name` IN (%s)
      LIMIT ' . count($names), $names);
  }

  /**
   * @param  array $names_or_rows Tag names or rows.
   * @return array Tag arrays as returned by Tag::get_array().
   */
  public function get_tag_arrays($names_or_rows) {
    if (is_array(current($names_or_rows))) { // rows
      $rows = $names_or_rows;
    } else { // names
      $rows = $this->get_tag_rows($names_or_rows);
    }

    return array_map(function ($row) {
      $tag = new Tag($this, $row);
      return $tag->get_array(false);
    }, $rows);
  }



  /**
   * @return array Array of all tag data, intended for JSON-encoded output.
   */
  public function get_tags_array() {
    $rows = $this->db->fetch_all("
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
    return array_map(function ($row) {
      return array(
        'id' => (int)$row['id'],
        'name' => $row['name'],
        'url' => $row['url'],
        'caption' => $row['caption'],
        'fileCount' => (int)$row['fileCount'],
        'parentCount' => (int)$row['parentCount'],
        'childCount' => (int)$row['childCount']
      );
    }, $rows);
  }

  /**
   * @return array Tree of all dir data, intended for JSON-encoded output.
   */
  public function get_dirs_array() {
    $rows = $this->db->fetch_all('
      SELECT
        `DIRS`.*,
        COUNT(`FILES`.`id`) AS `fileCount`
      FROM `DIRS` LEFT JOIN `FILES`
      ON `DIRS`.`id` = `FILES`.`dir_id`
      GROUP BY `DIRS`.`id`
      ORDER BY `DIRS`.`path`
    ');

    // typecasting
    $rows = array_map(function ($row) {
      return array(
        'id' => (int)$row['id'],
        'path' => $row['path'],
        'fileCount' => (int)$row['fileCount']
      );
    }, $rows);

    // filter out dirs with excluded paths
    $rows = array_filter($rows, function ($row) {
      return !$this->path_is_excluded($row['path']);
    });

    // convert to a tree with path components as keys; for example, the row for
    // a/b/c is stored in $tree['a']['subdirs']['b']['subdirs']['c']
    $tree = array();
    foreach ($rows as $row) {
      $components = explode(
        '/',
        $row['path'] === '.' ? '.' : "./{$row['path']}" // to put . at the top
      );

      $branch = array(
        array_pop($components) => $row
      );

      while ($component = array_pop($components)) {
        $branch = array(
          $component => array(
            'subdirs' => $branch
          )
        );
      }

      $tree = array_replace_recursive($tree, $branch);
    }
    return $tree;
  }
}

?>
