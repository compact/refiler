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
}
?>