<?php
/**
 * @author: Yosiet Serga
 * @website: www.necoyoad.com
 * @license: www.necoyoad.com
 * @email: yosiet.serga@necoyoad.com
 */

class Necoyoad_Service {
  private $api;

  public function __construct(Necoyoad_Api $api) {
    $this->api = $api;
  }

  public function getApi() {
    return $this->api;
  }
}
