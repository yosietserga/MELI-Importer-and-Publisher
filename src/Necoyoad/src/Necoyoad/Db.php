<?php

class Necoyoad_Db {
	private $driver;
	
	public function __construct($driver, $hostname, $username, $password, $database) {
		if (file_exists(__DIR__ .'/Database/Necoyoad_'. $driver . '.php')) {
			require_once(__DIR__ .'/Database/Necoyoad_'. $driver . '.php');
		} else {
			exit('Error: Could not load database file ' . $driver . '!');
		}
		$driver = 'Necoyoad_'. $driver;
		$this->driver = new $driver($hostname, $username, $password, $database);
	}
		
  	public function query($sql) {
		return $this->driver->query($sql);
  	}
	
	public function escape($value) {
		return $this->driver->escape($value);
	}
	
  	public function countAffected() {
		return $this->driver->countAffected();
  	}

  	public function getLastId() {
		return $this->driver->getLastId();
  	}
}
