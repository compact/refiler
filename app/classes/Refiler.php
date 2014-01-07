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



  //////////////////////////////////////////////////////////////// dir functions

  /**
   * @return array[] The dir rows stored in the db, along with the file count
   *   for each dir. 
   */
  public function get_dir_rows() {
    $rows = $this->db->fetch_all('
      SELECT `DIRS`.*, COUNT(`FILES`.`id`) AS `fileCount`
      FROM `DIRS` LEFT JOIN `FILES`
      ON `DIRS`.`id` = `FILES`.`dir_id`
      GROUP BY `DIRS`.`id`
      ORDER BY `DIRS`.`path`
    ');
    return $rows;
  }

  /**
   * @return Dir[] Array mapped from get_dir_rows().
   */
  public function get_dirs() {
    return array_map(function ($row) {
      return new Dir($this, $row);
    }, $this->get_dir_rows());
  }

  /**
   * @return string[] Array mapped from get_dir_rows().
   */
  public function get_dir_paths() {
    return array_map(function ($row) {
      return $row['path'];
    }, $this->get_dir_rows());
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
}
?>