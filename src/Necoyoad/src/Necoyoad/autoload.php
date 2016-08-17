<?php
/**
 * @author: Yosiet Serga
 * @website: www.necoyoad.com
 * @license: www.necoyoad.com
 * @email: yosiet.serga@necoyoad.com
 */


function necoyoad_api_php_client_autoload($className)
{
  $classPath = explode('_', $className);
  if ($classPath[0] != 'Necoyoad') {
    return;
  }

  $classPath = array_slice($classPath, 1, 2);

  $filePath = dirname(__FILE__) . '/' . implode('/', $classPath) . '.php';
  if (file_exists($filePath)) {
    require_once($filePath);
  }
}

spl_autoload_register('necoyoad_api_php_client_autoload');
