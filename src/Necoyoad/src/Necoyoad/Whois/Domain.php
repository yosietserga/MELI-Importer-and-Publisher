<?php

if (!class_exists('Necoyoad_Api')) {
    require_once dirname(__FILE__) . '/../autoload.php';
}


class Necoyoad_Whois_Domain {

    protected $data = array();

    function __construct($config = null) {
        $this->set('loader', new Necoyoad_Engine_Loader);
        $this->xhttp = $this->get('loader')->library('xhttp/xhttp', null, true);

        if (is_array($config) && !empty($config)) {
            $this->data = array_merge($this->data, $config);
        }
    }

    public function get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function set($key, $value) {
        $this->data[$key] = $value;
    }

    public function __get($key) {
        return isset($this->data[$key]) ? $this->data[$key] : null;
    }

    public function __set($key, $value) {
        $this->data[$key] = $value;
    }

    public function clear($key = null) {
        if ($key)
            unset($this->data[$key]);
        else
            unset($this->data);
    }

    public function validateDomain($domain) {
        return true;
    }

    public function checkDomain($domain) {
        if ($this->validateDomain($domain)) {
            $result = $this->xhttp->fetch('http://www.caracashosting.com/consulta/cwhoiscart.php?cwaction=lookup&domain=' . $domain, array(
                'method' => 'post',
                'post' => array(
                    'query' => $domain,
                ),
            ));

            if ($result['body']) {
                $this->body = $result['body'];
                if (strpos(strtolower($this->body), 'no match for "' . strtolower($domain) . '"')) {
                    $this->set('is_available', true);
                    return true;
                } else {
                    $this->set('is_available', false);
                    return false;
                }
            }
        } else {
            return false;
        }
    }

    public function isAvailable() {
        return $this->get('is_available');
    }

    public function suggest() {

    }

    public function getDateLastUpdate() {

    }

    public function getDateExpire() {

    }

    public function getDateRenovation() {

    }

    public function fetch() {

    }

}
