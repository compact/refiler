<?php

namespace Refiler;

/**
 * Database class that wraps around PDO.
 * 2013-04-17
 */
class DB {
  // config
  protected $table_prefix;

  protected $PDO;
  protected $queries;
  protected $query_count = 0;

  public function __construct($config) {
    $this->table_prefix = $config['table_prefix'];

    try {
      $this->PDO = new \PDO(
        "mysql:dbname={$config['name']};host={$config['host']};"
          . "port={$config['port']}",
        $config['user'],
        $config['pass'],
        array(\PDO::ATTR_PERSISTENT => false)
      );
    } catch (\PDOException $e) {
      trigger_error('Database connection failed.');
    }
  }

  public function get_PDO() {
    return $this->PDO;
  }

  /**
   * Use begin_transaction() and commit() for multiple insert/update/delete
   * queries in a script.
   */
  public function begin_transaction() {
    return $this->PDO->beginTransaction();
  }

  public function commit() {
    return $this->PDO->commit();
  }

  public function get_last_insert_id() {
    return $this->PDO->lastInsertId();
  }

  public function quote($string, $parameter_type = \PDO::PARAM_STR) {
    return $this->PDO->quote($string, $parameter_type);
  }

  /**
   * @param  string       $q_or_s Query, or statement with parameter markers.
   * @param  array|null   $params Parameter values for binding to a statement.
   * @return PDOStatement
   */
  public function query($q_or_s, $params = null) {
    // fake constants can be used in queries to improve code readability
    $prefix = $this->table_prefix;
    $q_or_s = str_replace('`DIRS`', "`{$prefix}dirs`", $q_or_s);
    $q_or_s = str_replace('`FILES`', "`{$prefix}files`", $q_or_s);
    $q_or_s = str_replace('`TAGS`', "`{$prefix}tags`", $q_or_s);
    $q_or_s = str_replace('`FILE_TAG_MAP`', "`{$prefix}file_tag_map`", $q_or_s);
    $q_or_s = str_replace('`TAG_MAP`', "`{$prefix}tag_map`", $q_or_s);

    $success = true;

    try {
      if (empty($params)) {
        $statement = $this->PDO->query($q_or_s);
      } else {
        $statement = $this->PDO->prepare($q_or_s);
        $success = $statement->execute($params);
      }
      if (!$statement || !$success) {
        $error = is_object($statement) ? $statement->errorInfo()[2] : '';
        throw new \Exception("Query failed: $error");
      } else {
        $this->query_count++;
        return $statement;
      }
    } catch (\Exception $e) {
      exit($e); // TODO
    }
  }

  /**
   * @param string $q_or_s Query, or statement with parameter markers.
   * @param array|null $params Parameter values for binding to a statement.
   * @return array All rows of the result.
   */
  public function fetch_all($q_or_s, $params = null) {
    return $this->query($q_or_s, $params)->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * @param string $query Query with an IN clause containing '(%s)' to be
   *   replaced by markers.
   * @param array $in_values Array of values to be bound. Keys do not matter.
   *   If multidimensional, each element is an array of values with a
   *   corresponding '%s'.
   * @param array $params Optional array of additional parameters to be bound.
   *   The markers must be '?' and after '(%s)'.
   */
  public function query_in($query, $in_values, $params = array()) {
    if (!is_multi_array($in_values)) { // simple case
      $markers = array_fill(0, count($in_values), '?');
      $markers = implode(',', $markers);
      $query = str_replace('%s', $markers, $query);
    } else { // multiple lists case
      foreach ($in_values as $values) {
        $markers = array_fill(0, count($values), '?');
        $markers = implode(',', $markers);
        $query = preg_replace('/%s/', $markers, $query, 1);
      }

      // flatten the multidimensional array
      // if the $use_keys param is true, then the arrays get merged instead
      $in_values = iterator_to_array(new \RecursiveIteratorIterator(
        new \RecursiveArrayIterator($in_values)), false); // flatten
    }

    $params = array_merge($in_values, $params);

    return $this->query($query, $params);
  }

  public function fetch_all_in($query, $in_values) {
    return $this->query_in($query, $in_values)->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Insert any number of rows into the given table.
   * @param string $table No sanitizing.
   * @param array $rows Must be two-dimensional. Loose sanitizing.
   * @param string $on_duplicate_key_update Optional. Adds
   *   'ON DUPLICATE KEY UPDATE `id` = `id`' to the end of the query.
   * @return PDOStatement|null Null if $rows is empty.
   */
  public function insert($table, $rows, $on_duplicate_key_update = false) {
    if (!is_array($rows)) {
      trigger_error('Invalid data.');
    } elseif (count($rows) === 0) {
      return null;
    }
    $first_row = reset($rows);
    if (!is_array($first_row)) {
      trigger_error('Invalid data.');
    }

    // build the query string; all param markers will be ?
    $fields = array_keys($first_row);
    $fields = array_map(function ($field) {
      return sprintf('`%s`', $field);
    }, $fields);
    $fields = implode(',', $fields);

    $row_markers = array_fill(0, count($first_row), '?');
    $row_markers = '(' . implode(',', $row_markers) . ')';
    $row_markers = array_fill(0, count($rows), $row_markers);
    $row_markers = implode(',', $row_markers);

    $query = "INSERT INTO `$table` ($fields) VALUES $row_markers";

    if ($on_duplicate_key_update) {
      $query .= ' ON DUPLICATE KEY UPDATE `id` = `id`';
    }

    // populate the params to be bound
    $params = iterator_to_array(new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($rows)
    ), false);

    return $this->query($query, $params);
  }

  public function get_query_count() {
    return $this->query_count;
  }
}

?>