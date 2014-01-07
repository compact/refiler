<?php

namespace Refiler;

/**
 * This class wraps around the cURL functions for convenient reading or saving
 * of external files.
 */
class Curl {
  protected $ch;
  protected $options = array(
    CURLOPT_RETURNTRANSFER => true, // return a string rather than output it
    CURLOPT_FAILONERROR => true,
    CURLOPT_CONNECTTIMEOUT => 3, // seconds to wait
    CURLOPT_LOW_SPEED_LIMIT => 5, // B/s
    CURLOPT_LOW_SPEED_TIME => 3, // s
    CURLOPT_FOLLOWLOCATION => true, // follow redirects
    CURLOPT_MAXREDIRS => 10
  );

  public function __construct($options = array()) {
    $this->ch = $this->init($options);
  }

  /**
   * Return a new cURL handle.
   */
  public function init($options) {
    $this->ch = curl_init();
    $this->options = $options + $this->options;
    foreach ($this->options as $option => $value) {
      curl_setopt($this->ch, $option, $value);
    }
    return $this->ch;
  }

  public function set($option, $value) {
    curl_setopt($this->ch, $option, $value);
  }

  public function save($url, $target, $close = false) {
    curl_setopt($this->ch, CURLOPT_URL, $url);
    $targeth = fopen($target, 'wb');
    curl_setopt($this->ch, CURLOPT_FILE, $targeth);
    $result = curl_exec($this->ch);
    fclose($targeth);

    if ($close) {
      curl_close($this->ch);
    }

    if (!$result || filesize($target) == 0) { // failure
      unlink($target);
      return false;
    } else {
      return true;
    }
  }

  public function read($url, $close = false) {
    curl_setopt($this->ch, CURLOPT_URL, $url);
    $return = curl_exec($this->ch);

    if ($close) {
      curl_close($this->ch);
    }
    return $return;
  }

  public function filesize($url, $close = false) {
    $this->set(CURLOPT_NOBODY, true); // no body
    $this->set(CURLOPT_HEADER, true);
    $header = $this->read($url, $close);

    if (preg_match('/Content-Length: (\d+)/', $header, $matches) === 1) {
      return $matches[1];
    } else {
      return false;
    }
  }
}

?>