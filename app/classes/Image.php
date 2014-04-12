<?php

namespace Refiler;

class Image extends File {
  protected $image_type;

  // set on the first call of either has_exif_thumb() or get_exif_thumb()
  protected $exif_thumb = null;
  protected $exif_thumb_width = null;
  protected $exif_thumb_height = null;

  public function __construct(Refiler $refiler, $param, $getimagesize) {
    parent::__construct($refiler, $param);

    list($this->width, $this->height, $this->image_type)
      = is_array($getimagesize)
        ? $getimagesize
        : getimagesize($this->get_path());

    if (empty($this->thumb_type)) {
      $this->thumb_type = NO_THUMB;
    }
  }

  public function is_image() {
    return true;
  }

  // unused as of 2013-12-19
  protected function get_thumb_type_in_filesystem() {
    $types = array('gif', 'jpg', 'png');
    foreach ($types as $type) {
      if (is_file($this->get_default_thumb_path($type))) {
        return $type;
      }
    }
    return NO_THUMB;
  }

  /**
   * @return false|string
   *   False on failure.
   *   Thumb type on success.
   *   SELF_THUMB if the image dimensions are smaller than the default
   *     thumbnail dimensions.
   *   NO_THUMB
   *   NO_THUMB_TOO_LARGE if the image will use up ~25% of memory_limit.
   */
  public function create_thumb($optimal_type = true) {
    $thumb_max_width = $this->config['max_thumb_width'];
    $thumb_max_height = $this->config['max_thumb_height'];

    $old_ratio = $this->width / $this->height;
    $max_ratio = $thumb_max_width / $thumb_max_height; // not necessarily the new ratio

    // special case: SELF_THUMB
    if (($this->width <= $thumb_max_width
        && $this->height <= $thumb_max_height)) {
      $this->thumb_type = SELF_THUMB;
      return $this->thumb_type;
    }

    // 1024 * 1024 / 4 / 2 = 131072 bytes
    // for ~4 bytes per pixel and 50% of memory_limit
    $pixel_limit = (int)ini_get('memory_limit') * 131072;
    if ($this->width * $this->height > $pixel_limit
        && !$this->has_exif_thumb()) {
      $this->thumb_type = NO_THUMB_TOO_LARGE;
      return $this->thumb_type;
    }

    // 1. new ratio
    if ($old_ratio / $max_ratio > 1.25 || $max_ratio / $old_ratio > 1.25) { // elongated
      $new_ratio = ($old_ratio + $max_ratio) / 2;
      $shrink_factor = .95;
    } else { // squarish
      $new_ratio = $max_ratio;
      $shrink_factor = .9;
    }

    // 2. new dimensions
    if ($old_ratio / $max_ratio > 1.25) {
      $new_width = $thumb_max_width;
      $new_height = $new_width / $new_ratio;
    } elseif ($max_ratio / $old_ratio > 1.25) {
      $new_height = $thumb_max_height;
      $new_width = $new_height * $new_ratio;
    } else {
      $new_width = $thumb_max_width;
      $new_height = $thumb_max_height;
    }
    // shrink if image is tall but too thin, or fat but too short
    if ($new_width > $this->width) {
      $shrink_factor = 1;
      $new_width = $this->width;
      $new_height = $new_width / $new_ratio;
    }
    if ($new_height > $this->height) {
      $shrink_factor = 1;
      $new_height = $this->height;
      $new_width = $new_height * $new_ratio;
    }
    $new_width = round($new_width);
    $new_height = round($new_height);

    // 3. crop border
    if ($old_ratio > $max_ratio) { // old image is fatter, so start with height
      $height_crop = $shrink_factor * $this->height;
      $width_crop = round($height_crop * $max_ratio);
      $height_crop = round($height_crop);
    } else {
      $width_crop = $shrink_factor * $this->width;
      $height_crop = round($width_crop / $max_ratio);
      $width_crop = round($width_crop);
    }

    // 4. coordinates
    $old_x = round(($this->width - $width_crop) / 2);
    $old_y = round(($this->height - $height_crop) / 4); // elevate since many images have faces near the top

    // 5. resize
    $success = $this->resize($this->get_default_thumb_path(),
      $thumb_max_width, $thumb_max_height,
      $old_x, $old_y, $width_crop, $height_crop,
      $optimal_type);
    $this->thumb_type = $success ? after($success, '.') : NO_THUMB_CORRUPT;
    return $this->thumb_type;
  }

  // not used 2013-04-27
  // save a scaled version of $source at $target; do nothing if dimensions are too large
  // imagecreatefromjpeg error on corrupt files: gd-jpeg, libjpeg: recoverable error: Premature end of JPEG file
  public function scale($source, $target, $scale) {
    $old_width = $this->width;
    $old_height = $this->height;

    $new_width = round($old_width * $scale);
    $new_height = round($old_height * $scale);

    if ($new_width == 0 || $new_height == 0
      || (defined('MAX_PIXELS') && (
        $old_width * $old_height > MAX_PIXELS
        || $old_width * $old_height * $scale * $scale > MAX_PIXELS
    ))) {
      return false;
    }

    return $this->resize($this->get_path(), $new_width, $new_height, 0, 0,
      $old_width, $old_height);
  }

  /**
   * Resize this image and save the result at $target. Corrupt images can
   * cause warnings, which are suppressed by the error handler:
   *
   * exif_thumbnail(name.jpg): Process tag(x0001=UndefinedTa): Illegal format code 0x0000, suppose BYTE
   * exif_thumbnail(name.jpg): Incorrect APP1 Exif Identifier Code
   * imagecreatefromjpeg(): gd-jpeg, libjpeg: recoverable error: Corrupt JPEG data: premature end of data segment
   * imagecreatefromjpeg(): gd-jpeg, libjpeg: recoverable error: Corrupt JPEG data: 154 extraneous bytes before marker 0xd9
   * imagecreatefromjpeg(): 'name.jpg' is not a valid JPEG file
   * imagecreatefromstring(): Data is not in a recognized format
   * libpng warning: Incorrect bKGD chunk length
   * libpng warning: Buffer error in compressed datastream in iCCP chunk
   *
   * @param string $target Save path. If $optimal_type is true, exclude the dot *   and extension.
   * @param int $old_width Not necessarily the full dimensions of the image.
   * @param int $old_height
   * @param boolean $optimal_type Whether to pick the best image type to
   *   minimize filesize. Only applies if $source is a gif, the worst format
   *   size-wise.
   * @return boolean|string Whether the resizing is successful. If successful
   *   and $optimal_type is true, return the target file path since the
   *   extension wasn't known.
   */
  public function resize($target,
      $new_width, $new_height,
      $old_x, $old_y,
      $old_width, $old_height,
      $optimal_type = false) {
    $dir = dirname($target);
    if (!is_dir($dir)) {
      mkdir($dir, 0755, true);
    }
    $path = $this->get_path();

    $success = false;
    switch ($this->image_type) {
      case IMAGETYPE_GIF:
        $old_image = imagecreatefromgif($path);
        if ($old_image) {
          // imagecreatetruecolor() doesn't work with gifs
          $new_image = $optimal_type
            ? imagecreatetruecolor($new_width, $new_height)
            : imagecreate($new_width, $new_height);

          $old_transparent_color = imagecolortransparent($old_image);
          if ($old_transparent_color !== -1) { // transparency exists
            if (!$optimal_type) {
              $target_type = 'gif';
              $new_transparent_color = imagecolorallocate(
                $new_image,
                $old_transparent_color['red'],
                $old_transparent_color['green'],
                $old_transparent_color['blue']
              );
              imagefill($new_image, 0, 0, $new_transparent_color);
              imagecolortransparent($new_image, $new_transparent_color);
            } elseif ($this->size < 32 * 1024) { // big images are not worth transparency at the cost of size
              $target_type = 'png';

              // if true, the transparent colour is visible as black
              imagealphablending($new_image, false);

              // if false, the transparent colour is visible
              imagesavealpha($new_image, true);
            } else {
              $target_type = 'jpg';
            }
          } else {
            $target_type = $optimal_type ? 'jpg' : 'gif';
          }
        }
        break;

      case IMAGETYPE_JPEG:
        // if there is an embedded thumb, use it instead of the original image
        if ($this->has_exif_thumb()) {
          $old_image = imagecreatefromstring($this->exif_thumb);
          // recalculate dimensions, x, and y
          $old_width = round($old_width * $this->exif_thumb_width / $this->width);
          $old_height = round($old_height * $this->exif_thumb_height / $this->height);
          $old_x = round(($this->exif_thumb_width - $old_width) / 2);
          $old_y = round(($this->exif_thumb_height - $old_height) / 4);
        } else {
          $old_image = imagecreatefromjpeg($path);
        }

        if ($old_image) {
          $new_image = imagecreatetruecolor($new_width, $new_height);

          // progressive JPEGs as thumbs are a little smaller
          // based on the sample I have tested
          imageinterlace($new_image, true);

          $target_type = 'jpg';
        }
        break;

      case IMAGETYPE_PNG:
        $old_image = imagecreatefrompng($path);
        if ($old_image) {
          $new_image = imagecreatetruecolor($new_width, $new_height);
          // imagecolortransparent($old_image) returns -1 for pngs
          imagealphablending($new_image, false); // if true, the transparent colour is visible as black
          imagesavealpha($new_image, true); // if false, the transparent colour is visible

          $target_type = 'png';
        }
        break;

      default:
        return false;
    }
    if ($old_image) {
      if ($optimal_type) {
        $target .= ".$target_type";
      }

      $this->resample(
        $new_image, $old_image, 0, 0, $old_x, $old_y,
        $new_width, $new_height, $old_width, $old_height
      );
      $success = $this->save_image($new_image, $target, $target_type);

      if ($success) {
        imagedestroy($old_image);
        imagedestroy($new_image);
        chmod($target, 0644);
        return $optimal_type ? $target: $success;
      }
    }
    return false;
  }

  /**
   * If the image is large enough, imagecopyresampled() takes up a lot of
   * resources. In this case, use imagecopyresized() first to create a
   * smaller temporary image before resampling. A little quality is
   * sacrificed.
   */
  protected function resample($new_image, $old_image, $new_x, $new_y,
      $old_x, $old_y, $new_width, $new_height, $old_width, $old_height) {
    if ($old_width > $new_width * 4 && $old_height > $new_height * 4) {
      $temp_width = $new_width * 4;
      $temp_height = $new_height * 4;
      $temp_image = imagecreatetruecolor($temp_width, $temp_height);
      imagecopyresized($temp_image, $old_image, 0, 0, $old_x, $old_y,
        $temp_width, $temp_height, $old_width, $old_height);
      return imagecopyresampled($new_image, $temp_image, $new_x, $new_y,
        0, 0, $new_width, $new_height, $temp_width, $temp_height);
    }

    return imagecopyresampled($new_image, $old_image, $new_x, $new_y,
      $old_x, $old_y, $new_width, $new_height, $old_width, $old_height);
  }

  protected function save_image($image, $target, $type) {
    switch ($type) {
      case 'gif':
        return imagegif($image, $target);
      case 'jpg':
        return imagejpeg($image, $target,
          $this->config['jpeg_quality']);
      case 'png':
        // compression levels 1-9 give similar filesizes
        // PNG_NO_FILTER gives the smallest filesize
        return imagepng($image, $target, 9, PNG_NO_FILTER);
    }
  }

  protected function has_exif_thumb() {
    return $this->get_exif_thumb() !== false;
  }

  protected function get_exif_thumb() {
    if ($this->exif_thumb === null) {
      $this->exif_thumb = @exif_thumbnail(
        $this->get_path(),
        $this->exif_thumb_width,
        $this->exif_thumb_height
      );
    }

    return $this->exif_thumb;
  }
}
