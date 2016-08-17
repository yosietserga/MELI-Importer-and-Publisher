<?php

require_once(__DIR__ .'/../Necoyoad/autoload.php');

class NecoyoadDomain {

    protected $data = array();
    protected $handler;

    public function __construct($config=null) {
        if (is_array($config) && !empty($config)) {
            $this->data = array_merge($this->data, $config);
        }
        $this->handler = new Necoyoad_Whois_Domain;
    }

    public function get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function clear($key) {
        if ($key) {
            unset($this->data[$key]);
         } else {
            unset($this->data);
        }
    }

    public function checkDomain($domain) {
        return $this->handler->checkDomain($domain);
    }

    public function logout($delete_messages=false) {

    }

    public function processEmail($emailid=0) {

    }

    public function deleteEmail($emailid) {

    }

    public function isPop() {

    }

    public function isImap() {

    }

    public function getEmailCount() {

    }
}