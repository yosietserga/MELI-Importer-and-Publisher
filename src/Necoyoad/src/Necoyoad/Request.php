<?php

class Necoyoad_Request {

    public $get = array();
    public $post = array();
    public $cookie = array();
    public $files = array();
    public $server = array();

    public function __construct() {
        $_GET = $this->clean($_GET);
        $_POST = $this->clean($_POST);
        $_REQUEST = $this->clean($_REQUEST);
        $_COOKIE = $this->clean($_COOKIE);
        $_FILES = $this->clean($_FILES);
        $_SERVER = $this->clean($_SERVER);

        $this->get = $_GET;
        $this->post = $_POST;
        $this->request = $_REQUEST;
        $this->cookie = $_COOKIE;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function clean($data) {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                unset($data[$key]);

                $data[$this->clean($key)] = $this->clean($value);
            }
        } else {
            $data = htmlspecialchars($data, ENT_COMPAT);
        }

        return $data;
    }

    public function setQuery($key, $value) {
        $this->get[$key] = $value;
    }

    public function getQuery($key) {
        return ($this->hasQuery($key)) ? trim($this->get[$key]) : null;
    }

    public function hasQuery($key) {
        return !empty($this->get[$key]);
    }

    public function setCookie($key, $value) {
        $this->cookie[C_CODE . "_" . $key] = $value;
    }

    public function getCookie($key) {
        return $this->cookie[C_CODE . "_" . $key];
    }

    public function hasCookie($key) {
        return isset($this->cookie[C_CODE . "_" . $key]);
    }

    public function setPost($key, $value) {
        $this->post[$key] = $value;
    }

    public function getPost($key) {
        return $this->post[$key];
    }

    public function hasPost($key) {
        return isset($this->post[$key]);
    }

}
