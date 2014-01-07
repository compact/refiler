<?php

namespace Refiler;

class Logger {
  protected $start_time; // for measuring script execution time
  protected $time; // used by benchmark
  protected $db;
  protected $content_type;

  public function __construct() {
    $this->time = microtime(true);
    $this->start_time = $this->time;
  }

  public function set_content_type($content_type) {
    $this->content_type = $content_type;
    header("Content-type: $content_type");
  }

  public function benchmark($text = '') {
    $line_break = $this->content_type === 'text/plain'
      ? "\n" : "\n\n<br>\n\n";

    $html = '';
    if (!empty($text)) {
      $html .= $text;
      $html .= $line_break;
    }
    if ($this->db === null && $GLOBALS['db']) {
      $this->db = $GLOBALS['db'];
    }
    if (is_object($this->db)) {
      $html .= sprintf("Query count: %u\t\t", $this->db->get_query_count());
    }
    $html .= sprintf("Execution time: %.4F s", microtime(true) - $this->time);
    $html .= $line_break;

    $this->time = microtime(true);

    return $html;
  }

  // execution stats for admins
  public function footer() {
    global $db, $execution_time;

    $line_break = $this->content_type === 'text/plain'
      ? "\n" : "\n\n<br><br>\n\n";

    $total_execution_time_needed = $this->time !== $this->start_time;

    $html = $line_break;
    $html .= $this->benchmark();

    if ($total_execution_time_needed) {
      $html .= sprintf("Total execution time: %.4F s", microtime(true) - $this->start_time);
      $html .= $line_break;
    }

    $html .= sprintf("Memory usage: %.2F MB",
      memory_get_usage() / 1048576, 2);
    $html .= $line_break;

    return $html;
  }
}

?>