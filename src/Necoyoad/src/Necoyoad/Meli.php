<?php

require_once(__DIR__.'/../../vendor/xhttp/xhttp.php');
require_once(__DIR__.'/../../vendor/meli/meli.php');

class Necoyoad_Meli {

    public $app_id;
    public $app_secret;
    public $token;
    public $refresh_token;
    public $expire;
    public $code;
    public $meli_id;

    private $handler;
    private $meli;
    private $redirectTo;
    private $request;
    private $session;
    private $oauth_url;


    public function __construct($app_id, $app_secret) {
        if (class_exists('xhttp')) {
            $this->handler = new xhttp;
        } else {
            $this->handler = new thisHandler;
        }
        $this->app_id = $app_id;
        $this->app_secret = $app_secret;
        $this->meli = new Meli($this->app_id, $this->app_secret);
        $this->request = new Necoyoad_Request;
        $this->session = new Necoyoad_Session;
    }

    public function initialize() {
        $this->oauth_url = $this->meli->getAuthUrl($this->getRedirectUrl());
    }

    public function setRedirectUrl($url) {
        $this->redirectTo = $url;
    }

    public function getRedirectUrl() {
        return $this->redirectTo;
    }

    public function index() {
        $mlactions = array(
            'import_products',
            'publish_products',
            'add_profile'
        );

        $this->initialize();

        if ($this->request->hasQuery('action')) {
            $_SESSION['mlaction'] = $this->request->getQuery('action');
        }

        if ($this->request->getQuery('action') == 'add_profile') {
            $this->clear_code();
            $this->clear_token();
            $this->clear_refresh_token();
            $this->clear_expire();
            $this->redirect();
        }

        if ($this->request->hasQuery('code') && !$this->get_code()) {
            $response = $this->authorize($this->request->getQuery('code'), $this->getRedirectUrl());
            $this->set_code( $this->request->getQuery('code') );
            $response['body'] = (object)json_decode($response['body']);

            if (isset($response['body']->access_token)) $this->set_token( $response['body']->access_token );
            if (isset($response['body']->expires_in)) $this->set_expire( time() + $response['body']->expires_in );
            if (isset($response['body']->refresh_token)) $this->set_refresh_token( $response['body']->refresh_token );
            unset($_GET['code']);
        }

        if (!$this->get_code() && $this->request->hasQuery('meli')) {
            $this->clear_code();
            $this->clear_token();
            $this->clear_refresh_token();
            $this->clear_expire();
            $this->redirect();
        }

        $this->check_expire( $this->get_expire() );

        if ($this->get_token()) {
            if (isset($_SESSION['mlaction']) && in_array($_SESSION['mlaction'], $mlactions)) {
                $result = $this->{$_SESSION['mlaction']}();
            } else {
                $this->clear_code();
                $this->clear_token();
                $this->clear_refresh_token();
                $this->clear_expire();
            }
        } else {
            $this->clear_code();
            $this->clear_token();
            if ($this->request->hasQuery('meli')) {
                $this->redirect();
            }
        }

        if (isset($_REQUEST['logout'])) {
            $this->clear_code();
            $this->clear_token();
            $this->clear_refresh_token();
            $this->clear_expire();
        }

        if ($this->request->hasQuery('error')) {
            return array(
                'error'=>$this->request->getQuery('error'),
                'error_description'=>$this->request->getQuery('error_description')
            );
        } elseif (isset($result)) {
            return $result;
        }
    }

    public function clear_code() {
        unset($this->code);
        unset($_SESSION['mlcode']);
    }

    public function clear_token() {
        unset($this->token);
        unset($_SESSION['mltoken']);
    }

    public function clear_meli_id() {
        unset($this->meli_id);
        unset($_SESSION['mlmeli_id']);
    }

    public function clear_refresh_token() {
        unset($this->refresh_token);
        unset($_SESSION['mlrefresh_token']);
    }

    public function clear_expire() {
        unset($this->expire);
        unset($_SESSION['mlexpire']);
    }

    public function get_code() {
        return isset($this->code) ? $this->code : $_SESSION['mlcode'];
    }

    public function set_code($code) {
        $this->code = $_SESSION['mlcode'] = $code;
    }

    public function get_token() {
        return isset($this->token) ? $this->token : $_SESSION['mltoken'];
    }

    public function set_token($token) {
        $this->token = $_SESSION['mltoken'] = $token;
    }

    public function get_refresh_token() {
        return isset($this->refresh_token) ? $this->refresh_token : $_SESSION['mlrefresh_token'];
    }

    public function set_refresh_token($refresh_token) {
        $this->refresh_token = $_SESSION['mlrefresh_token'] = $refresh_token;
    }

    public function get_expire() {
        return isset($this->expire) ? $this->expire : $_SESSION['mlexpire'];
    }

    public function set_expire($time) {
        $this->expire = $_SESSION['mlexpire'] = $time;
    }

    public function get_meli_id() {
        return isset($this->meli_id) ? $this->meli_id : $_SESSION['mlmeli_id'];
    }

    public function set_meli_id($meli_id) {
        $this->meli_id = $_SESSION['mlmeli_id'] = $meli_id;
    }

    public function check_expire($time) {
        if(isset($time) && $time < time()) {
            // Make the refresh proccess
            $refresh = $this->refreshToken();

            if (isset($refresh->error)) {
                return array(
                    'error'=>$refresh->error,
                    'status'=>$refresh->status,
                    'message'=>$refresh->message
                );
            } else {
                // Now we create the sessions with the new parameters
                $this->set_token( $refresh->access_token );
                $this->set_refresh_token( $refresh->refresh_token );
                $this->set_expire( time() + $refresh->expires_in );
            }
        }
    }

    public function add_profile() {
        if ($this->get_token()) {
            $response = $this->fetch('/users/me');
            return $this->addCustomerFromMeli(array(
                'company'     => $response['body']->nickname,
                'firstname'   => $response['body']->first_name,
                'lastname'    => $response['body']->last_name,
                'email'       => $response['body']->email,
                'meli_id'     => $response['body']->id,
                'meli_token'  => $_SESSION['mltoken'],
                'meli_refresh'=> $_SESSION['mlrefresh_token'],
                'meli_expire' => $_SESSION['mlexpire'],
                'meli_code'   => $_SESSION['mlcode']
            ));
        } else {
            $this->redirect();
        }
    }

    public function delete_profile($id) {
        $this->deleteCustomerFromMeli($id);
    }

    public function get_profiles($data = null) {
        return $this->getProfiles($data);
    }

    public function get_products($data = null) {
        return $this->getProducts($data);
    }

    public function get_activities($data = null) {
        return $this->getActivities($data);
    }

    public function import_products() {
        if ($this->get_token() && $this->get_meli_id()) {
            if (!$db || !($db instanceof Necoyoad_Db)) {
                $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
            }
            $response = $this->fetch("/users/{$this->get_meli_id()}/items/search");

            $existente = 0;
            $nuevo = 0;
            $total = count($response['body']->results);
            foreach ($response['body']->results as $k => $v) {
                $resp = $this->fetch("/items/{$v}");
                $desc = $this->fetch("/items/{$v}/description");

                if ($resp['body']) {
                    $meli_product_id = $resp['body']->id;
                    $query = $db->query("SELECT * AS total FROM ". DB_PREFIX ."product WHERE `meli_product_id` = '" . $db->escape($meli_product_id) . "'");
                    if (empty($query->rows)) {
                        $data = array();
                        $c = 0;

                        $folder = __DIR__.'/../../../../images/data';
                        foreach ($resp['body']->pictures as $j => $img) {
                            if (!$img->url) continue;
                        
                            $fc = file_get_contents($img->url);
                            if (!is_dir($folder)) {
                                mkdir($folder,0777);
                            }

                            if (!is_dir($folder .'/'. date('m-y'))) {
                                mkdir($folder .'/'. date('m-y'), 0777);
                            }

                            $img_name = 'meli-'. $this->get_meli_id() .'-'. time().mt_rand(100000,9999999) . (substr($img->url, strrpos($img->url, '.')));
                            $img_file = $folder .'/'. date('m-y') .'/'. $img_name;
                            $f = fopen($img_file, 'w+');
                            fwrite($f, $fc);
                            fclose($f);
                        
                            $data['Images'][$c]['source'] = 'http://rumat.com.ar/newapi/images/data/'. date('m-y') .'/'. $img_name;
                            $c++;
                        }

                        $varis = array();
                        foreach ($resp['body']->variations as $i => $variation) {
                            foreach ($variation->attribute_combinations as $j => $combination) {
                                $varis[$i]['attribute_combinations'][$j] = array(
                                    'id'=>$combination->id,
                                    'value_id'=>$combination->value_id
                                );
                            }
                            $varis[$i]['available_quantity'] = $variation->available_quantity;
                            $varis[$i]['price'] = $variation->price;
                            $varis[$i]['picture_ids'] = $variation->picture_ids;
                        }

                        $db->query("INSERT INTO ". DB_PREFIX ."product SET ".
                            "`meli_id` = '". $db->escape($this->get_meli_id()) ."',".
                            "`meli_product_id` = '". $db->escape($meli_product_id) ."',".
                            "`meli_category_id` = '". $db->escape($resp['body']->category_id) ."',".
                            "`name` = '". $db->escape($resp['body']->title) ."',".
                            "`currency_id` = '". $db->escape($resp['body']->currency_id) ."',".
                            "`available_quantity` = '". $db->escape($resp['body']->available_quantity) ."',".
                            "`buying_mode` = '". $db->escape($resp['body']->buying_mode) ."',".
                            "`listing_type_id` = '". $db->escape($resp['body']->listing_type_id) ."',".
                            "`condition` = '". $db->escape($resp['body']->condition) ."',".
                            "`description` = '". $db->escape($desc['body']->text) ."',".
                            "`price` = '". $db->escape($resp['body']->price) ."',".
                            "`image` = '". str_replace("'","\'",serialize($data['Images'])) ."'".
                            (!$varis) ? '' : ",`variations` = '". str_replace("'","\'",serialize($varis)) ."'"
                        );
                        
                        $id = $db->getLastId();
                        
                        $db->query("INSERT INTO `". DB_PREFIX ."property` SET ".
                            "`object_id` = '". $id ."',".
                            "`object_type` = 'product',".
                            "`group` = 'meli',".
                            "`key` = 'product_data',".
                            "`value` = '". serialize($resp['body']) ."'"
                        );
                        
                        $this->addActivity(array(
                            'description'=>'Se import&oacute; con &eacute;xito el producto '. $resp['body']->title .' del perfil '. $this->get_meli_id(),
                            'type'=>'import',
                            'object_id'=>$this->get_meli_id(),
                            'object_type'=>'customer'
                        ));
                        $nuevo++;
                    } else {
                        $existente++;
                        $this->addActivity(array(
                            'description'=>'Intento de importar el producto '. $resp['body']->title .' del perfil '. $this->get_meli_id() .', ya existe!',
                            'type'=>'import',
                            'object_id'=>$this->get_meli_id(),
                            'object_type'=>'customer'
                        ));
                    }
                    if (!empty($_SESSION['reportToEmail'])) {
                        mail($_SESSION['reportToEmail'], "ML Importer",
                            'Importancion de producto ' .
                            '. MESSAGE: ' .
                            "\n\r-------------------- PRODUCT IMPORTED -----------------------\n\r".
                            serialize($varis).
                            "\n\r-------------------- /PRODUCT IMPORTED ----------------------\n\r");
                    }
                }
            }

            $this->addActivity(array(
                'description'=>'Se importaron '. $total .' productos del perfil '. $this->get_meli_id() .'. '. $nuevo .' productos nuevos y '. $existente .' productos existentes',
                'type'=>'import',
                'object_id'=>$this->get_meli_id(),
                'object_type'=>'customer'
            ));

            if (!empty($_SESSION['reportToEmail'])) {
                mail($_SESSION['reportToEmail'], "ML Importer", 'Se importaron ' . $total . ' productos del perfil ' . $this->get_meli_id() . '. ' . $nuevo . ' productos nuevos y ' . $existente . ' productos existentes a las ' . date('d-m-Y h:s:i'));
            }
        } else {
            $this->redirect();
        }
    }

    public function publish_products($data) {
        if ($this->get_token() && $this->get_meli_id()) {
            $requestData['method'] = 'post';
            $requestData['headers']['Content-Type'] = 'application/json';
            $requestData['post'] = str_replace('\\','',$data);

            $response = $this->fetch("/items", $requestData);
            if (isset($response['json']['error'])) {
                $this->addActivity(array(
                    'description'=>'No se pudo publicar el producto '. $data['title'] .' en el perfil '. $this->meli_id .'. Error: '. $response['json']['error'] .' - '. $response['json']['message'] .' - '. $response['json']['status'],
                    'type'=>'publish',
                    'object_id'=>$this->get_meli_id(),
                    'object_type'=>'customer'
                ));

                if (!empty($_SESSION['reportToEmail'])) {
                    mail($_SESSION['reportToEmail'], "ML Importer", 'No se pudo publicar el producto ' . $data['title'] . ' en el perfil ' . $this->meli_id . '. Error: ' . $response['json']['error'] . ' - ' . $response['json']['message'] . ' - ' . $response['json']['status'] . ' - ' . serialize($response['json']['cause']) );
                }
                return $response['json'];
            } else {
                $this->addActivity(array(
                    'description'=>'Se public&oacute; con &eacute;xito el producto '. $data['tile'] .' en el perfil '. $this->meli_id,
                    'type'=>'publish',
                    'object_id'=>$this->get_meli_id(),
                    'object_type'=>'customer'
                ));
                if (!empty($_SESSION['reportToEmail'])) {
                    mail($_SESSION['reportToEmail'], "ML Importer", 'Se public&oacute; con &eacute;xito el producto ' . $data['tile'] . ' en el perfil ' . $this->meli_id);
                }
            }
        } else {
            $this->redirect($this->oauth_url);
        }
    }

    public function escape($str) {
        if (isset($str)) {
            if ($str !== mb_convert_encoding(mb_convert_encoding($str, 'UTF-32', 'UTF-8'), 'UTF-8', 'UTF-32'))
                $str = mb_convert_encoding($str, 'UTF-8', mb_detect_encoding($str));
            $str = htmlentities($str, ENT_NOQUOTES, 'UTF-8');
            $str = preg_replace('`&([a-z]{1,2})(acute|uml|circ|grave|ring|cedil|slash|tilde|caron|lig);`i', '\1', $str);
            $str = html_entity_decode($str, ENT_NOQUOTES, 'UTF-8');
            $str = preg_replace(array('`[^a-z0-9]`i', '`[-]+`'), '-', $str);
            $str = strtolower(trim($str, '-'));
            return $str;
        } else {
            return false;
        }
    }

    private function addCustomerFromMeli($data, $db = null) {
        if (!$db || !($db instanceof Necoyoad_Db)) {
            $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
        }
        if (!$this->getCustomerByMeli($data, $db)) {
            $sql = "INSERT INTO " . DB_PREFIX . "customer SET " .
                "`firstname` = '" . $db->escape($data['firstname']) . "'," .
                "`lastname` = '" . $db->escape($data['lastname']) . "'," .
                "`company` = '" . $db->escape($data['company']) . "'," .
                "`email` = '" . $db->escape($data['email']) . "'," .
                "`meli_id` = '" . $db->escape($data['meli_id']) . "'," .
                "`meli_token` = '" . $db->escape($data['meli_token']) . "'," .
                "`meli_refresh_token` = '" . $db->escape($data['meli_refresh']) . "'," .
                "`meli_expire` = '" . $db->escape($data['meli_expire']) . "'," .
                "`meli_code` = '" . $db->escape($data['meli_code']) . "'";
            $query = $db->query($sql);
            $id = $db->getLastId();

            $this->addActivity(array(
                'description'=>'Se agreg&oacute; con &eacute;xito un nuevo perfil '. $data['meli_id'] .' '. $data['company'],
                'type'=>'new_profile',
                'object_id'=>$data['meli_id'],
                'object_type'=>'customer'
            ));

            if (!empty($_SESSION['reportToEmail'])) {
                mail($_SESSION['reportToEmail'], "ML Importer", 'Se agreg&oacute; con &eacute;xito un nuevo perfil ' . $data['meli_id'] . ' ' . $data['company']);
            }

            return $id;
        } else {
            return false;
        }
    }

    private function deleteCustomerFromMeli($id, $db = null) {
        if (!$db || !($db instanceof Necoyoad_Db)) {
            $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
        }

        $db->query("DELETE FROM " . DB_PREFIX . "property WHERE ".
            "`object_id` IN ".
            "(SELECT meli_id FROM " . DB_PREFIX . "customer WHERE `id` = '" . $db->escape($id) . "') ".
            "AND object_type = 'customer'");

        $db->query("DELETE FROM " . DB_PREFIX . "property WHERE ".
            "`object_id` IN ".
            "(SELECT meli_id FROM " . DB_PREFIX . "customer WHERE `meli_id` = '" . $db->escape($id) . "') ".
            "AND object_type = 'customer'");

        $db->query("DELETE FROM " . DB_PREFIX . "property WHERE ".
            "`object_id` NOT IN  (SELECT id FROM " . DB_PREFIX . "product) ".
            "AND object_type = 'product'");

        $db->query("DELETE FROM " . DB_PREFIX . "customer WHERE `meli_id` = '" . $db->escape($id) . "'");
        $db->query("DELETE FROM " . DB_PREFIX . "product WHERE `meli_id` = '" . $db->escape($id) . "'");

        $this->addActivity(array(
            'description'=>'Se elimin&oacute; con &eacute;xito perfil '. $id,
            'type'=>'delete_profile',
            'object_id'=>$id,
            'object_type'=>'customer'
        ));
        if (!empty($_SESSION['reportToEmail'])) {
            mail($_SESSION['reportToEmail'], "ML Importer", 'Se elimin&oacute; con &eacute;xito perfil ' . $id);
        }
    }

    private function getProfiles($data = null, $db = null) {
        if (!$db || !($db instanceof Necoyoad_Db)) {
            $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
        }

        $sql = "SELECT * FROM `". DB_PREFIX ."customer` ";

        if (!empty($data['meli_id'])) {
            $criteria[] = " LCASE(`meli_id`) LIKE '%". $db->escape(strtolower($data['meli_id'])) ."%'";
        }

        if (!empty($data['email'])) {
            $criteria[] = " LCASE(`email`) LIKE '%". $db->escape(strtolower($data['email'])) ."%'";
        }

        if (!empty($data['company'])) {
            $criteria[] = " LCASE(`company`) LIKE '%". $db->escape(strtolower($data['company'])) ."%'";
        }

        if (!empty($criteria)) {
            $sql .= " WHERE ". implode(" AND ", $criteria);
        }

        $query = $db->query($sql);

        return $query->rows;
    }

    private function getProducts($data = null, $db = null) {
        if (!$db || !($db instanceof Necoyoad_Db)) {
            $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
        }

        $sql = "SELECT * FROM `". DB_PREFIX ."product` ";

        if (!empty($data['meli_id'])) {
            $criteria[] = " LCASE(`meli_id`) LIKE '%". $db->escape(strtolower($data['meli_id'])) ."%'";
        }

        if (!empty($data['meli_product_id'])) {
            $criteria[] = " LCASE(`meli_product_id`) LIKE '%". $db->escape(strtolower($data['meli_product_id'])) ."%'";
        }

        if (!empty($data['name'])) {
            $criteria[] = " LCASE(`name`) LIKE '%". $db->escape(strtolower($data['name'])) ."%'";
        }

        if (!empty($criteria)) {
            $sql .= " WHERE ". implode(" AND ", $criteria);
        }

        $query = $db->query($sql);

        return $query->rows;
    }

    private function getActivities($data = null, $db = null) {
        if (!$db || !($db instanceof Necoyoad_Db)) {
            $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
        }

        $sql = "SELECT * FROM `". DB_PREFIX ."activity` ";

        if (!empty($data['type'])) {
            $criteria[] = " LCASE(`type`) = '". $db->escape(strtolower($data['meli_id'])) ."'";
        }

        if (!empty($data['object_type'])) {
            $criteria[] = " LCASE(`object_type`) = '". $db->escape(strtolower($data['object_type'])) ."'";
        }

        if (!empty($criteria)) {
            $sql .= " WHERE ". implode(" AND ", $criteria);
        }

        if (isset($data['sort'])) {
            $sql .= " ORDER BY `". $data['sort'] ."` ";
        } else {
            $sql .= " ORDER BY `date_added` ";
        }

        if (isset($data['order'])) {
            $sql .= $data['order'];
        } else {
            $sql .= " DESC ";
        }

        if (isset($data['start']) && isset($data['limit'])) {
            $sql .= " LIMIT ". (int)$data['start'] .",". (int)$data['limit'];
        } elseif (isset($data['limit'])) {
            $sql .= " LIMIT ". (int)$data['limit'];
        }

        $query = $db->query($sql);

        return $query->rows;
    }

    private function getCustomerByMeli($data, $db = null) {
        if (!$db || !($db instanceof Necoyoad_Db)) {
            $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
        }
        $sql = "SELECT * FROM ". DB_PREFIX ."customer WHERE ".
            "`email` = '". $db->escape($data['email']) ."' ".
            "OR `meli_id` = '". $db->escape($data['meli_id']) ."'";

        $query = $db->query($sql);

        return $query->num_rows;
    }

    private function redirect() {
        echo "<script>location.href = '{$this->oauth_url}'</script>";
    }

    private function fetch($uri, $data=null) {
        $url = 'https://api.mercadolibre.com';

        $response = $this->handler->fetch($url . $uri . '?access_token=' . $_SESSION['mltoken'], $data);

        $response['body'] = (object)json_decode($response['body']);
        return $response;
    }

    private function authorize($code, $redirect_uri) {
        $requestData['method'] = 'post';
        $requestData['post'] = 'code=' . $code
            . '&client_id=' . $this->app_id
            . '&client_secret=' . $this->app_secret
            . '&redirect_uri=' . urlencode($redirect_uri)
            . '&grant_type=authorization_code';

        $this->addActivity(array(
            'description'=>'Se solicit&oacute; acceso a un perfil con el ML Code '. $code .'',
            'type'=>'authorization_code',
            'object_id'=>$this->get_meli_id(),
            'object_type'=>'customer'
        ));
        return $this->handler->fetch('https://api.mercadolibre.com/oauth/token', $requestData);
    }

    private function refreshToken() {
        $requestData['method'] = 'post';
        $requestData['post'] = 'refresh_token=' . $this->refresh_token
            . '&client_id=' . $this->app_id
            . '&client_secret=' . $this->app_secret
            . '&grant_type=refresh_token';

        $response = $this->handler->fetch('https://api.mercadolibre.com/oauth/token', $requestData);
        if (isset($response['json']['error'])) {
            $this->addActivity(array(
                'description'=>'No se pudo renovar el token para el perfil '. $this->meli_id .'. No existe o es incorrecto. Error: '. $response['json']['error'] .' - '. $response['json']['message'] .' - '. $response['json']['status'],
                'type'=>'refresh_token',
                'object_id'=>$this->get_meli_id(),
                'object_type'=>'customer'
            ));
            return (object)$response['json'];
        } else {
            $this->addActivity(array(
                'description'=>'Se renov&oacute; con &eacute;xito el token para el perfil '. $this->meli_id,
                'type'=>'refresh_token',
                'object_id'=>$this->get_meli_id(),
                'object_type'=>'customer'
            ));
            return (object)json_decode($response['body']);
        }
    }

    private function addActivity($data, $db = null) {
        if (!$db || !($db instanceof Necoyoad_Db)) {
            $db = new Necoyoad_Db(DB_DRIVE, DB_HOST, DB_USER, DB_PWD, DB_NAME);
        }

        $sql = "INSERT INTO `". DB_PREFIX ."activity` SET ".
        "`object_id` = '". $db->escape($data['object_id']) ."',".
        "`object_type` = '". $db->escape($data['object_type']) ."',".
        "`description` = '". $db->escape($data['description']) ."',".
        "`icon` = '". $db->escape($data['icon']) ."'";

        $query = $db->query($sql);
    }

}

class thisHandler {
    public function fetch($url, $options = null) {
        return $this->request($url, $options);
    }

    private function request($url, $options = null) {
        if (!isset($options['method']) || !in_array($options['method'], array('GET','POST','PUT','DELETE'))) {
            $options['method'] = 'GET';
        }

        if (isset($options['content']) && !empty($options['content'])) {
            if (is_array($options['content'])) {
                $options['content'] = http_build_query($options['content']);
            } else {
                $options['content'] = urlencode($options['content']);
            }
        }

        $context  = stream_context_create($options);
        $resp['url'] = $url;
        $resp['request'] = $context;
        $resp['body'] = file_get_contents($url, false, $context);
        return $resp;
    }

}
