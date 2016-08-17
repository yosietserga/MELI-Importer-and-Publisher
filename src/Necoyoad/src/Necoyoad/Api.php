<?php
/**
 * @author: Yosiet Serga
 * @website: www.necoyoad.com
 * @license: www.necoyoad.com
 * @email: yosiet.serga@necoyoad.com
 */

if (!class_exists('Necoyoad_Api')) {
  require_once dirname(__FILE__) . '/autoload.php';
}

class Necoyoad_Api {
  const LIBVER = "0.0.1";

  protected $registry = array();

  public function __construct($config = null) {

  }

  public function __get($key) {
    return $this->registry[$key] || null;
  }

  public function __set($key, $value) {
    $this->registry[$key] = $value;
  }

  public function clear($key) {
    unset($this->registry[$key]);
  }

  public function getLibraryVersion() {
    return self::LIBVER;
  }
}
