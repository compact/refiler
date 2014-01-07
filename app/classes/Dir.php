<?php

namespace Refiler;

/**
 * Each instance of this class corresponds to a directory. The dir does not
 *   have to exist either in the filesystem or as a row in the `DIRS` table.
 */
class Dir {
  // injected dependencies
  protected $refiler;
  protected $db;

  // fields in the `DIRS` table
  protected $id = null; // not necessarily set in the constructor; doesn't
                        // exist if the dir doesn't have a row
  protected $path;

  // stored arrays for resource intensive methods
  protected $file_rows = null; // rows as stored in the db
  protected $files = null; // files found in the dir; keys are filenames
  // protected $subdir_paths = null; // array, not used



  /**
   * In a constructed object, $this->path is always set, but $this->id may not
   *   be.
   * @param Refiler          $refiler
   * @param int|string|array $path
   *   Case 1: Integer, an id in the `DIRS` table.
   *   Case 2: String, a path relative to the base path, which gets sanitized.
   *     '' and '.' are both considered the base path.
   *   Case 3: Array, a row from the `DIRS` table. Does not get sanitized.
   */
  public function __construct(Refiler $refiler, $param) {
    $this->refiler = $refiler;
    $this->db = $refiler->get_db();

    if (is_int($param)) {
      $id = $param;

      $this->id = $id;
      $this->path = $this->db->fetch_all('
        SELECT `path` FROM `DIRS`
        WHERE `id` = ?
      ', array($id))[0]['path'];
    } elseif (is_string($param)) {
      $path = $param;

      $this->path = self::sanitize_path($path);
    } elseif (is_array($param)) {
      $row = $param;

      $this->id = $row['id'];
      $this->path = $row['path'];
    }
  }



  /**
   * @return int|boolean False if this dir does not have a row in the db.
   */
  public function get_id() {
    if ($this->id === null) {
      // if the id is not set in the constructor, it is set here
      $rows = $this->db->fetch_all('
        SELECT `id` FROM `DIRS`
        WHERE `path` = ?
        LIMIT 1
      ', array($this->path));

      $this->id = count($rows) === 1 ? (int)$rows[0]['id'] : false;
    }

    return $this->id;

    // throw new \Exception("Dir '{$this->path}' not found in the database.");

    // $this->db->insert('DIRS', array(array(
    //   'path' => $this->path
    // )));
    // $this->id = $this->db->get_last_insert_id();
    // return $this->id;
  }

  /**
   * Create the dir and insert a row. Neither must exist already.
   * @return boolean Whether both operations succeeded.
   */
  public function create_and_insert() {
    if (is_dir($this->path) || $this->id !== null) {
      return false;
    }

    // create
    mkdir($this->path, 0755, true);
    mkdir($this->get_thumbs_path(), 0755, true);

    // insert
    $this->db->insert('DIRS', array(array(
      'path' => $this->path
    )));
    $this->id = $this->db->get_last_insert_id();

    return true;
  }

  /**
   * @return string
   */
  public function get_path() {
    return $this->path;
  }

  public function get_thumbs_path() {
    return "thumbs/{$this->path}";
  }

  /**
   * @param string $path May include the filename.
   */
  public static function sanitize_path($path) {
    $path = trim($path);

    // .. or \ or leading /
    if (preg_match('/(\.\.|\\|^\/)/', $path)) {
      throw new \Exception("Invalid path: '$path'.");
    }

    // remove ./
    $path = str_replace('./', '', $path);

    if (empty($path)) {
      return '.';
    }

    // replace repeated /
    $path = preg_replace('/\/+/', '/', $path);

    return $path;
  }

  public function exists_in_filesystem() {
    return is_dir($this->path);
  }



  /**
   * Do not call this method more than once.
   * @param  boolean $recursive
   * @param  boolean $include_this Whether to include the row of this dir.
   * @return array[]
   */
  public function get_subdir_rows($recursive = false, $include_this = false,
      $ids_as_keys = false) {
    if ($this->path === '.') {
      // special case: base dir
      if ($recursive) {
        $rows = $this->db->fetch_all("
          SELECT * FROM `DIRS`
          WHERE `path` != '.'
        ");
      } else {
        $rows = $this->db->fetch_all('
          SELECT * FROM `DIRS`
          WHERE `path` REGEXP ?
        ', array("^[^/.]+$"));
      }
    } else {
      // general case: every other dir
      if ($recursive) {
        $rows = $this->db->fetch_all('
          SELECT * FROM `DIRS`
          WHERE `path` LIKE ?
        ', array("{$this->path}/%"));
      } else {
        $rows = $this->db->fetch_all('
          SELECT * FROM `DIRS`
          WHERE `path` REGEXP ?
        ', array("^{$this->path}/[^/]+$"));
      }
    }

    if ($include_this) {
      // include this dir row
      $id = $this->get_id();
      if ($id !== false) {
        // it is possible that this dir isn't inserted yet
        $rows[] = array(
          'id' => $id,
          'path' => $this->path
        );
      }
    }

    return $rows;
  }

  /**
   * @param  boolean $recursive
   * @param  boolean $include_this Whether to include this dir.
   * @param  boolean $ids_as_keys  Whether the returned array will have ids as
   *   keys, which can be used for convenient lookup.
   * @return Dir[]   Array mapped from get_subdir_rows().
   */
  public function get_subdirs($recursive = false, $include_this = false,
      $ids_as_keys = false) {
    $subdirs = array();
    $rows = $this->get_subdir_rows($recursive, $include_this);

    foreach ($rows as $row) {
      $dir = new Dir($this->refiler, $row);

      if (!$ids_as_keys) {
        $subdirs[] = $dir;
      } else {
        $subdirs[$row['id']] = $dir;
      }
    }

    return $subdirs;
  }

  /**
   * @return array Array of recursive subdir paths that actually exist in the
   *   filesystem. Paths are relative to the base path, not to the current
   *   dir's path.
   */
  public function get_subdir_paths_in_filesystem() {
    // recursive helper function starting at a given subdir path
    $get_dir_paths = function ($dir_path) use (&$get_dir_paths) {
      $dir_paths = array($dir_path);
      $handle = opendir($dir_path);

      while ($file_name = readdir($handle)) {
        $file_path = $dir_path !== '.' ? "$dir_path/$file_name" : $file_name;

        if (is_dir($file_path) && $file_name !== '.' && $file_name !== '..'
            && !$this->refiler->path_is_excluded($file_path)) {
          $dir_paths = array_merge($dir_paths, $get_dir_paths($file_path));
        }
      }

      closedir($handle);
      return $dir_paths;
    };

    return $get_dir_paths($this->path);
  }



  /**
   * This function should be called only once, for performance reasons.
   * @param  boolean $recursive
   * @return array   An array of files that are actually in the directory
   *   currently, independent of rows in the `FILES` table.
   */
  public function get_files_in_filesystem($recursive = false) {
    $files = array();
    $handle = opendir($this->path);

    while ($name = readdir($handle)) {
      $path = File::path($this->path, $name);

      if (!$this->refiler->path_is_excluded($path)) { // filter excluded paths
        if (is_file($path)) {
          // add the file to the resulting array
          $files[] = File::new_file_or_image($this->refiler, array(
            'dir' => $this,
            'name' => $name
          ));
        } elseif ($recursive && is_dir($path) && $name !== '.'
            && $name !== '..') {
          // recursive case
          $subdir = new Dir($this->refiler, $path);
          $files = array_merge($files, $subdir->get_files_in_filesystem($recursive));
        }
      }
    }

    closedir($handle);
    return $files;
  }

  /**
   * @param  boolean $recursive TODO: Used only on the first call.
   * @return array[] An array of rows from the database. This array is only
   *   generated once; all subsequent calls return the same array without
   *   checking for updates.
   */
  public function get_file_rows($recursive = false) {
    if ($this->file_rows === null) {
      if (!$recursive) {
        // non-recursive case
        $this->file_rows = $this->db->fetch_all('
          SELECT * FROM `FILES`
          WHERE `dir_id` = ?
          ORDER BY `name`
        ', array($this->get_id()));
      } else {
        // recursive case
        $this->file_rows = $this->db->fetch_all_in('
          SELECT * FROM `FILES`
          WHERE `dir_id` IN (%s)
        ', array_map(function ($subdir) {
          return $subdir->get_id();
        }, $this->get_subdirs(true, true)));
      }
    }
    return $this->file_rows;
  }

  /**
   * @param  boolean $recursive
   * @return File[]  Array mapped from get_file_rows().
   */
  public function get_files($recursive = false) {
    if (!$recursive) {
      // non-recursive case
      return array_map(function ($row) {
        // build file properties by adding the dir
        $properties = $row;
        $properties['dir'] = $this;

        return File::new_file_or_image($this->refiler, $properties);
      }, $this->get_file_rows(false));
    } else {
      // recursive case
      $subdirs_by_id = $this->get_subdirs(true, true, true); // keys are ids

      return array_map(function ($row) use ($subdirs_by_id) {
        // build file properties by adding the dir
        $properties = $row;
        $properties['dir'] = $subdirs_by_id[$row['dir_id']];

        return File::new_file_or_image($this->refiler, $properties);
      }, $this->get_file_rows(true));
    }
  }



  /**
   * @return array Rows of tags shared by all files in the dir.
   */
  public function get_common_tag_rows() {
    $tag_rows = array();
    $file_rows = $this->get_file_rows(false);
    $file_count = count($file_rows);
    if ($file_count > 0) {
      $file_ids = multi_array_values($file_rows, 'id');
      $tag_rows = $this->db->fetch_all_in("
        SELECT `TAGS`.`name`,
        COUNT(`TAGS`.`id`) AS `file_count`
        FROM `TAGS` JOIN `FILE_TAG_MAP` ON `TAGS`.`id` = `FILE_TAG_MAP`.`tag_id`
        WHERE `FILE_TAG_MAP`.`file_id` IN (%s)
        GROUP BY `TAGS`.`id`
        HAVING `file_count` = $file_count
      ", $file_ids);
    }
    return $tag_rows;
  }



  /**
   * Move this dir to the given path. Update the row and the rows of all
   *   subdirs.
   * @param string $new_path Gets sanitized.
   */
  public function move($new_path) {
    // get old paths
    $old_path = $this->get_path();
    $old_thumbs_path = $this->get_thumbs_path();

    // get new paths
    $new_path = self::sanitize_path($new_path);
    $new_thumbs_path = "thumbs/$new_path"; // TODO: shouldn't hardcode this here

    // do not move if the new path is the same
    if ($old_path === $new_path) {
      return false;
    }

    // do not move if the new path already exists
    if (file_exists($new_path)) { // is_file() or is_dir()
      throw new \Exception("Cannot move to $new_path because it already exists.");
    }

    // move dir
    $success = rename($old_path, $new_path);
    if (!$success) {
      throw new \Exception("Failed to move $old_path to $new_path.");
    }

    // move thumbs dir
    if (is_dir($old_thumbs_path) && !file_exists($new_thumbs_path)) {
      rename($old_thumbs_path, $new_thumbs_path);
    }

    // get subdirs (before the row for this dir is updated)
    $subdirs = $this->get_subdirs(true, false); // recursive, not including this

    // update row
    $this->db->query('
      UPDATE `DIRS`
      SET `path` = ?
      WHERE `id` = ?
      LIMIT 1
    ', array($new_path, $this->get_id()));

    // update subdir rows
    $preg_quoted_old_path = preg_quote($old_path, '/'); // escape the slashes
    foreach ($subdirs as $subdir) {
      $new_subdir_path = preg_replace(
        "/^$preg_quoted_old_path/",
        $new_path,
        $subdir->get_path()
      );

      $this->db->query('
        UPDATE `DIRS`
        SET `path` = ?
        WHERE `id` = ?
        LIMIT 1
      ', array($new_subdir_path, $subdir->get_id()));
    }

    $this->path = $new_path;
  }

  /**
   * Delete this dir, including subdirs, files, and all associated rows.
   */
  public function delete() {
    // delete dir recursively
    if ($this->exists_in_filesystem()) {
      recursive_rmdir($this->get_path());
    }

    // delete rows
    $subdirs_by_id = $this->get_subdirs(true, true, true);
    $subdir_ids = array_keys($subdirs_by_id);
    $this->db->query_in('
      DELETE FROM `DIRS`
      WHERE `id` IN (%s)
    ', $subdir_ids);
  }

  /**
   * Update the rows in the `DIRS` table corresponding to the files in this dir.
   *   The files determine the rows, not the other way around. For example, if
   *   there is a file with no row, insert a row (rather than delete the file).
   *   If there is a row for a file that doesn't exist, delete the row.
   * @param  array $options
   *   'recursive'     Whether to update subdirectories recursively.
   *   'deep_update'   Whether to use File::deep_compare() to
   *                   check whether file properties need to be updated.
   *   'create_thumbs' Whether to create thumbs for images. Takes a long time.
   *   'file_limit'    Limit for get_files_in_filesystem() to reduce execution
   *                   time.
   *                   Disables deletion of rows with no corresponding files.
   *                   0 for no limit.
   *   'query_limit'   Limit for each type of operation (update, insert, and
   *                   delete) to reduce execution time.
   *                   0 for no limit.
   * @return array Array including the rows that have been updated, inserted,
   * and deleted.
   */
  public function update($options) {
    $options += array(
      'log' => false,
      'recursive' => false,
      'deep_update' => false,
      'create_thumbs' => false,
      'file_limit' => 10000,
      'query_limit' => 1000
    );

    // start logging
    if ($options['log']) {
      $logger = $this->refiler->get_logger();
      echo $logger->benchmark('update() start');
    }

    // get files from the db
    $files = $this->get_files($options['recursive']);
    if ($options['log']) {
      echo $logger->benchmark('after get_files(): ' . count($files) . ' files');
    }

    // get files in the filesystem
    $files_in_fs = $this->get_files_in_filesystem($options['recursive']);
    if ($options['log']) {
      echo $logger->benchmark('after get_files_in_filesystem(): '
        . count($files_in_fs) . ' files');
    }

    // calculate the results
    $results = array(
      'update' => $options['deep_update']
        ? array_uintersect($files_in_fs, $files, '\Refiler\File::deep_compare')
        : array(),
      'insert' => array_udiff($files_in_fs, $files, '\Refiler\File::compare'),
      'delete' => array_udiff($files, $files_in_fs, '\Refiler\File::compare')
    );
    if ($options['query_limit'] > 0) {
      foreach ($results as $type => $files_in_fs) {
        $results[$type] = array_slice($files_in_fs, 0, $options['query_limit']);
      }
    }
    if ($options['log']) {
      echo $logger->benchmark('update() after udiffs');
    }



    // deep update
    if (count($results['update'] > 0)) {
      foreach ($results['update'] as $file) {
        // update row
        $statement = $file->update();
        if ($options['log']) {
          echo "rows updated for {$file->get_path()}:";
          echo "{$statement->rowCount()}\n";
        }
      }
    }
    if ($options['log']) {
      echo $options['deep_update'] ?
        $logger->benchmark('update() after updates') : '';
    }



    // task 2: create thumbs for files with no thumbs
    if ($options['create_thumbs']) {
      $file_ids_by_thumb_type = array(); // used to reduce query count

      // create thumbs
      foreach ($files as $file) {
        if ($file->is_image() && $file->get_thumb_type() === NO_THUMB) {
          $thumb_type = $file->create_thumb();

          if ($thumb_type !== NO_THUMB) {
            $file_ids_by_thumb_type[$thumb_type][] = $file->get_id();
          }

          if ($options['log']) {
            echo "created thumb for {$file->get_path()}\n";
          }
        }
      }

      // update rows with the new thumb types
      foreach ($file_ids_by_thumb_type as $thumb_type => $ids) {
        if (count($ids) > 0) {
          $statement = $this->db->query_in("
            UPDATE `FILES`
            SET `thumb_type` = '$thumb_type'
            WHERE `id` IN (%s)
          ", $ids);

          if ($options['log']) {
            echo "rows updated for thumb type $thumb_type:";
            echo " {$statement->rowCount()}\n";
          }
        }
      }
    }



    // task 3: insert files that don't have rows
    if (count($results['insert']) > 0) {
      $insert_rows = array();
      foreach ($results['insert'] as $file) {
        // create thumb if needed
        if ($options['create_thumbs'] && $file->is_image()) {
          $file->create_thumb();

          if ($options['log']) {
            echo "created thumb for {$file->get_path()}\n";
          }
        }

        // convert file to row
        $insert_rows[] = $file->generate_row();
      }
      // insert
      $statement = $this->db->insert('FILES', $insert_rows);

      if ($options['log']) {
        echo "{$statement->rowCount()} rows inserted for {$this->path}\n";
      }
    }
    if ($options['log']) {
      echo $logger->benchmark('update() after inserts');
    }



    // task 4: delete rows for files that don't exist in the filesystem
    if (count($results['delete']) > 0) {
      $row_count = count($results['delete']);

      $ids = array();
      foreach ($results['delete'] as $file) {
        $ids[] = $file->get_id();
      }
      $ids = implode(',', $ids);

      $query = "DELETE FROM `FILES`
        WHERE `id` IN ($ids)
        LIMIT $row_count";
      $statement = $this->db->query($query);

      if ($options['log']) {
        echo "{$statement->rowCount()} rows deleted for {$this->path}\n";
      }
    }
    if ($options['log']) {
      echo $logger->benchmark('update() after deletes, done');
    }
  }
}

?>