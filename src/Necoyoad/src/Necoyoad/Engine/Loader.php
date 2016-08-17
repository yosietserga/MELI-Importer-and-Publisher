<?php

final class Necoyoad_Engine_Loader {

    protected $registry;

    public function __construct() {
        if (!defined('DIR_APPLICATION')) {
            exit('DIR_APPLICATION is not defined');
            return false;
        }
    }

    public function library($library, $args = null, $return = false) {
        $file = __DIR__ . '/../../../vendor/' . $library . '.php';

        if (file_exists($file)) {
            include_once($file);
            $paths = explode('/', $library);
            $class = end($paths);

            if ($return && class_exists($class)) {
                $obj = ($args) ? new $class($args) : new $class;
                return $obj;
            }
        } else {
            exit('Error: Could not load library ' . $library . '!');
        }
    }

    public function model($model, $return = false) {
        $file = DIR_APPLICATION . 'model/' . $model . '.php';
        $classPath = explode('/', $model);
        $class = preg_replace('/[^a-zA-Z0-9]/', '', end($classPath));

        if (file_exists($file)) {
            include_once($file);
            if ($return && class_exists($class)) {
                return new $class;
            }
        } else {
            exit('Error: Could not load model ' . $model . '!');
        }
    }
}
