<?php

abstract class Necoyoad_Engine_Controller {

    protected $registry;
    protected $id;
    protected $template;
    protected $templatePath = null;
    protected $children = array();
    protected $data = array();
    protected $widget = array();
    protected $output;
    protected $cacheId = null;

    public function __construct($registry) {
        if (!defined('DIR_APPLICATION')) {
            exit('DIR_APPLICATION is not defined');
            return false;
        }

        $this->registry = $registry;
    }

    public function __get($key) {
        return $this->registry->get($key);
    }

    public function __set($key, $value) {
        $this->registry->set($key, $value);
    }

    /**
     * Controller::setvar()
     * Asigna un valor a una variable 
     * @param string $varname
     * @param array $model
     * @return mixed $varname
     * */
    public function setvar($varname, $model = null, $default = null) {
        if (isset($this->request->post[$varname])) {
            $this->data[$varname] = $this->request->post[$varname];
        } elseif (isset($model)) {
            $this->data[$varname] = $model[$varname];
        } elseif (isset($this->request->get[$varname])) {
            $this->data[$varname] = $this->request->get[$varname];
        }elseif (isset($default)) {
            $this->data[$varname] = $default;
        } else {
            $this->data[$varname] = '';
        }
        return $this->data[$varname];
    }

    public function getvar($varname) {
        return isset($this->data[$varname]) ? $this->data[$varname] : false;
    }

    protected function forward($route, $args = array()) {
        return new Necoyoad_Engine_Action($route, $args);
    }

    protected function redirect($url) {
        if (!headers_sent()) {
            header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url));
            exit;
        } else {
            echo "<script> window.location = '".str_replace('&amp;', '&', $url)."'; </script>";
        }
    }

    protected function render($return = false) {
        foreach ($this->children as $key => $child) {
            $action = new Necoyoad_Engine_Action($child);
            $file = $action->getFile();
            $class = $action->getClass();
            $method = $action->getMethod();
            $args = $action->getArgs();
            
            if (file_exists($file)) {
                require_once($file);
                $controller = new $class($this->registry);
                $controller->index($this->widget[$key]);
                if (!is_numeric($key)) {
                    $this->data[$key . "_hook"] = $key;
                    $this->data[$key . "_code"] = $controller->output;
                } else {
                    $this->data[$controller->id] = $controller->output;
                }
            } else {
                exit('Error: Could not load controller ' . $child . '!');
            }
        }

        if ($return) {
            $r = $this->fetch($this->template);
            return $r;
        } else {
            $this->output = $this->fetch($this->template);
        }
    }

    protected function fetch($filename) {
        if ($this->templatePath && is_dir($this->templatePath)) {
            $file = $this->templatePath . $filename;
        } else {
            $file = DIR_APPLICATION .'view/'. $filename .'.tpl';
        }
        
        if (file_exists($file)) {
            //$this->data['Url'] = new Url($this->registry);
            
            extract($this->data);
            ob_start();
            require($file);
            $content = ob_get_contents();
            ob_end_clean();
            
            $content = str_replace("\n", "", $content);
            $content = str_replace("\r", "", $content);
            $content = preg_replace('/\s{2,}/', "", $content);
            $content = preg_replace('/\n\s*\n/', "\n", $content);
            
            return $content;
        } else {
            exit('Error: Could not load template ' . $file . '!');
        }
    }

    public function renderChild($view) {
        if (file_exists(DIR_APPLICATION .'view/'. $view .".tpl")) {
            include_once(DIR_APPLICATION .'view/'. $view .".tpl");
        } else {
            echo "No se pudo cargar el archivo $view";
        }
    }
}
