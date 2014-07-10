<?php

namespace Refiler;

/**
 * Each instance of this class corresponds to a file. The file does not have
 *   to exist either in the filesystem or as a row in the `FILES` table. A
 *   subclass is Image.
 */
class File {
  // injected dependencies
  protected $refiler;
  protected $config;
  protected $db;

  // properties corresponding to columns in the `FILES` table, except `dir_id`
  protected $id = null; // not necessarily set in the constructor, since the
                        // file may not have a row in the db yet
  protected $name = '';
  protected $type = ''; // sanitized extension
  protected $caption = '';
  protected $date = '';
  protected $size = 0;
  protected $width = 0;
  protected $height = 0;
  protected $thumb_type = NO_THUMB; // constant

  // Dir object; we store this instead of dir_id and dir_path
  protected $dir = null;



  /**
   * This constructor throws exceptions.
   * @param Refiler               $refiler
   * @param int|array $param
   *   Case 1: Int, an id from the `FILES` table.
   *   Case 2: Array of file properties. 'dir' and 'name' are required. The
   *     rest are optional and filled in automatically.
   */
  public function __construct(Refiler $refiler, $param) {
    // injected dependencies
    $this->refiler = $refiler;
    $this->config = $refiler->get_config('file');
    $this->db = $refiler->get_db();

    if (is_int($param)) {
      // case 1
      $id = $param;

      $rows = $this->db->fetch_all('
        SELECT
          `FILES`.*,
          `DIRS`.`path` AS `dir_path`
        FROM `FILES` LEFT JOIN `DIRS`
        ON `FILES`.`dir_id` = `DIRS`.`id`
        WHERE `FILES`.`id` = ?
        LIMIT 1
      ', array($id));

      if (count($rows) === 0) {
        throw new \Exception('File with given id not found.');
      }

      $properties = $rows[0];
      $properties['dir'] = new Dir($refiler, array(
        'id' => $properties['dir_id'],
        'path' => $properties['dir_path']
      ));
    } elseif (is_array($param)) {
      // case 2
      $properties = $param;
    } else {
      throw new \Exception('Invalid argument passed to the File constructor.');
    }

    // optional property: id
    if (isset($properties['id'])) {
      // may not be an int, such as when a row array is passed in directly
      $this->id = (int)$properties['id'];
    }

    // required property: name
    $this->name = $properties['name'];

    // required property: dir
    if (isset($properties['dir'])) {
      $this->dir = $properties['dir'];
    } else {
      // only case 1 can throw this exception
      throw new \Exception('Dir not given to the File constructor.');
    }

    // optional properties: type, date, size, which are found with SplFileInfo
    if (!isset($properties['type']) || !isset($properties['date'])
        || !isset($properties['size'])) {
      $spl_file_info = new \SplFileInfo($this->get_path());

      $properties['type'] = self::sanitize_type($spl_file_info->getExtension());
      $properties['date'] = date('Y-m-d H:i:s', $spl_file_info->getMTime());
      $properties['size'] = $spl_file_info->getSize();
    }
    $this->type = $properties['type'];
    $this->date = $properties['date'];
    $this->size = (int)$properties['size'];

    // remaining optional parameters
    $this->caption = isset_or($properties['caption'], '');
    $this->width = (int)isset_or($properties['width'], 0);
    $this->height = (int)isset_or($properties['height'], 0);
    $this->thumb_type = isset_or($properties['thumb_type'], NO_THUMB);
  }

  /**
   * Factory returning a new File or Image depending on whether the file is an
   *   image. Throws exceptions.
   * @param  Refiler $refiler
   * @param  array   $properties
   * @return File|Image
   */
  public static function new_file_or_image(Refiler $refiler, $properties) {
    if (!is_array($properties)) {
      throw new \Exception('Invalid argument passed to the File/Image factory.');
    }

    $path = self::path($properties['dir']->get_path(), $properties['name']);

    if (!isset($properties['type'])) {
      // the type is needed to determine if the file is an image
      $properties['type'] = self::sanitize_type_by_name($properties['name']);
    }

    // if the file exists and it has an image extension, then use the Image
    // constructor; a case where the file may not exist in the filesystem is
    // when a row has to be deleted
    $image_extensions = $refiler->get_config('file')['image_extensions'];
    if (in_array($properties['type'], $image_extensions) && is_file($path)) {
      // getimagesize() is a bottleneck; avoid it (and this factory) whenever
      // possible
      $getimagesize = getimagesize($path);

      if ($getimagesize) {
        return new Image($refiler, $properties, $getimagesize);
      }
    }

    return new File($refiler, $properties);
  }



  /**
   * All images must be Image instances. See new_file_or_image() for
   *   where images are actually identified.
   * @return boolean
   */
  public function is_image() {
    return false;
  }



  /**
   * @return int
   */
  public function get_id() {
    return $this->id;
  }

  /**
   * This method can be used when inserting this file without insert().
   * @param int $id
   */
  public function set_id($id) {
    $this->id = (int)$id;
  }

  /**
   * Sanitize the given filename at the given path.
   * @param string  $dir_path
   * @param string  $name
   * @param boolean $rename_on_duplicate If true and a file already exists at
   *   the given path, return a suitable replacement name (by adding a number
   *   at the end).
   */
  public static function sanitize_name($dir_path, $name,
      $rename_on_duplicate = true) {
    // sanitize extension
    $ext = self::sanitize_type_by_name($name);

    // sanitize filename without extension
    $basename = before_last($name, '.');
    $basename = preg_replace('/[\W"\']+/', '-', $basename);
    $basename = trim($basename, "- \t\n\r\0\x0B");

    $name = $ext !== '' ? "$basename.$ext" : $basename;

    // rename on duplicate
    if ($rename_on_duplicate && is_file("$dir_path/$name")) {
      if (preg_match('/\d{1,9}$/', $basename, $matches)) {
        // if the filename ends in a number, increment that number; we restrict
        // it to 4 digits for practical reasons, and to avoid overflow
        $old_number = $matches[0];

        // special case: leading 0s
        $prefix = '';
        if (preg_match('/^0+/', $old_number, $matches)) {
          $prefix = $matches[0];
        }

        // increment
        $new_number = (int)$old_number + 1;

        // special case: digit limit exceeded... TODO
        if ($new_number === 1000000000) {
          $new_number = '999999999-2';
        }

        $basename = before_last($basename, $old_number) . $prefix . $new_number;
      } else {
        $basename .= '2';
      }

      $name = $ext !== '' ? "$basename.$ext" : $basename;
      $name = self::sanitize_name($dir_path, $name);
    }

    return $name;
  }

  /**
   * @return string
   */
  public function get_name() {
    return $this->name;
  }

  /**
   * @return string
   */
  public function get_path() {
    return self::path($this->dir->get_path(), $this->name);
  }

  /**
   * @param  string $dir_path
   * @param  string $name
   * @return string
   */
  public static function path($dir_path, $name) {
    return $dir_path !== '.' ? "$dir_path/$name" : $name;
  }

  /**
   * @return string
   */
  public function get_date() {
    return $this->date;
  }

  /**
   * @return string
   */
  public function get_type() {
    return $this->type;
  }

  /**
   * @param  string $type
   * @return string
   */
  public static function sanitize_type($type) {
    $type = before($type, '?'); // remove any url query parameters
    $type = before($type, ':'); // e.g. Twitter uses 'name.jpg:large'
    $type = strtolower($type);
    if ($type === 'jpeg') { // special case: jpeg
      $type = 'jpg';
    }
    return $type;
  }

  /**
   * @param  string $name
   * @return string Empty string for a filename with no dot.
   */
  public static function sanitize_type_by_name($name) {
    return self::sanitize_type(after($name, '.', false));
  }

  /**
   * @param  string $caption
   * @return string
   */
  public static function sanitize_caption($caption) {
    $caption = trim($caption);
    $caption = preg_replace('/\r\n?/', "\n", $caption);
    return $caption;
  }

  public function get_caption() {
    return $this->caption;
  }

  public function set_caption($caption) {
    $this->caption = self::sanitize_caption($caption);
  }

  /**
   * @return int
   */
  public function get_size() {
    return $this->size;
  }

  /**
   * @return int
   */
  public function get_width() {
    return $this->width;
  }

  /**
   * @return int
   */
  public function get_height() {
    return $this->height;
  }

  /**
   * @return string
   */
  public function get_thumb_type() {
    return $this->thumb_type;
  }

  /**
   * As of May 2013, gif thumbs are either jpgs or pngs. To avoid thumb path
   *   conflicts with existing jpgs and pngs of the same name, add a prefix in
   *   the form 'gif-thumb-name.ext'. When changing this filename format, edit
   *   RefilerFile.prototype.getThumbPath() accordingly.
   * @param  string $ext Desired extension for the thumb. If not given, the
   *   path returned has a filename with no extension (no dot).
   * @return string The default thumb path. Images that are too large or serve
   *   as their own thumbs do not need this.
   */
  public function get_default_thumb_path($ext = '') {
    $name = before_last($this->name, '.');
    $prefix = 'thumb';
    if ($this->type === 'gif') {
      $prefix = "gif-$prefix";
    }
    if (!empty($ext)) {
      $ext = ".$ext";
    }
    return "thumbs/{$this->dir->get_path()}/$prefix-$name$ext";
  }

  /**
   * @return string
   */
  public function get_thumb_path() {
    switch ($this->thumb_type) {
      case SELF_THUMB:
        return $this->get_path();
      case NO_THUMB:
      case NO_THUMB_TOO_LARGE:
      case NO_THUMB_CORRUPT:
        return '';
      default:
        return $this->get_default_thumb_path($this->thumb_type);
    }
  }

  /**
   * @return boolean Whether a separate thumb file exists for this file. For
   *   example, if the file's thumb is itself, return false.
   */
  public function has_external_thumb() {
    return in_array($this->thumb_type, array('gif', 'jpg', 'png'));
  }

  public function get_dir() {
    return $this->dir;
  }



  /**
   * @return array An array for outputting in JSON.
   */
  public function get_array() {
    return array(
      'id' => $this->id,
      'dirPath' => $this->dir->get_path(),
      'name' => $this->name,
      'type' => $this->type,
      'caption' => $this->caption,
      'date' => $this->date,
      'size' => $this->size,
      'width' => $this->width,
      'height' => $this->height,
      'thumb_type' => $this->thumb_type
    );
  }

  /**
   * @return array A row for inserting into the db.
   */
  public function generate_row() {
    return array(
      'dir_id' => $this->dir->get_id(),
      'name' => $this->name,
      'type' => $this->type,
      'date' => $this->date,
      'size' => $this->size,
      'width' => $this->width,
      'height' => $this->height,
      'thumb_type' => $this->thumb_type
    );
  }

  /**
   * Insert a row for this file.
   */
  public function insert() {
    $this->db->insert('FILES', array($this->generate_row()));

    // set id
    $this->id = $this->db->get_last_insert_id();
  }

  /**
   * The id case is not used.
   * @return PDOStatement
   */
  public function update() {
    $query = 'UPDATE `FILES`
      SET `type` = :type,
      `date` = :date,
      `size` = :size,
      `width` = :width,
      `height` = :height,
      `thumb_type` = :thumb_type';
    $query .= $this->id !== null
      ? ' WHERE `id` = :id'
      : ' WHERE `dir_id` = :dir_id AND `name` = :name';
    $query .= ' LIMIT 1';

    $params = array(
      ':type' => $this->type,
      ':date' => $this->date,
      ':size' => $this->size,
      ':width' => $this->width,
      ':height' => $this->height,
      ':thumb_type' => $this->thumb_type
    );
    if ($this->id !== null) {
      $params[':id'] = $this->id;
    } else {
      $params[':dir_id'] = $this->dir->get_id();
      $params[':name'] = $this->name;
    }

    $statement = $this->db->query($query, $params);
    return $statement;
  }

  /**
   * This method throws exceptions. This file is not moved if the new path
   *   already exists. If this file is moved, its type gets updated.
   * @param string  $new_dir_path Gets sanitized.
   * @param string  $new_name     Gets sanitized, and does not rename on
   *   duplicate since that is not this method's responsibility.
   * @param boolean $update       Whether to update the database row. Set to
   *   false in the case of moving multiple files so only one query can be
   *   executed.
   */
  public function move($new_dir_path, $new_name, $update = true) {
    // get old paths
    $old_path = $this->get_path();
    $old_thumb_path = $this->get_thumb_path();

    // get new dir
    $this->dir = new Dir($this->refiler, $new_dir_path);

    // sanitize name, do not rename on duplicate
    $this->name = self::sanitize_name($this->dir->get_path(), $new_name, false);

    // get new path
    $new_path = $this->get_path();
    $new_thumb_path = $this->get_thumb_path();

    // do not move if the new path is the same
    if ($old_path === $new_path) {
      return false;
    }

    // do not move if the new path already exists
    if (file_exists($new_path)) { // is_file() or is_dir()
      if (is_dir($new_path)) {
        throw new \Exception("$new_path is a folder.");
      }
      throw new \Exception("Cannot move to $new_path because it already exists.");
    }

    // move file
    $success = rename($old_path, $new_path);
    if (!$success) {
      throw new \Exception("Failed to move $old_path to $new_path.");
    }

    // move thumb
    if ($this->has_external_thumb() && is_file($old_thumb_path)) {
      rename($old_thumb_path, $new_thumb_path);
    }

    // update type
    $this->type = self::sanitize_type_by_name($this->name);

    // update db
    if ($update) {
      $this->db->query('
        UPDATE `FILES`
        SET `dir_id` = ?, `name` = ?
        WHERE `id` = ?
        LIMIT 1
      ', array($this->dir->get_id(), $this->name, $this->id));
    }
  }

  /**
   * Delete this file, its thumb, and its row.
   */
  public function delete() {
    // delete row
    $statement = $this->db->query('
      DELETE FROM `FILES`
      WHERE `id` = ?
      LIMIT 1
    ', array($this->id));

    // delete file
    if (is_file($this->get_path())) {
      unlink($this->get_path());
    }

    // delete thumb
    if ($this->has_external_thumb() && is_file($this->get_thumb_path())) {
      unlink($this->get_thumb_path());
    }
  }



  /**
   * Compare two files by path.
   * @param  File $file1
   * @param  File $file2
   * @return int
   */
  public static function compare(File $file1, File $file2) {
    return strcmp($file1->get_path(), $file2->get_path());
  }

  /**
   * Compare two files by path, date, and size.
   * @param  File $file1
   * @param  File $file2
   * @return int
   */
  public static function deep_compare(File $file1, File $file2) {
    $compare = self::compare($file1, $file2);

    if ($compare !== 0) {
      return $compare;
    } elseif ($file1->get_date() !== $file2->get_date()
        || $file1->get_size() != $file2->get_size()) {
      // TODO: maybe compare width and height if those fields exist
      return 0;
    } else {
      return 1;
    }
  }
}

?>
