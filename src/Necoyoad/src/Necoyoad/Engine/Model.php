<?php
abstract class Necoyoad_Engine_Model {
	protected $registry;
	
	public function __construct($registry) {
		$this->registry = $registry;
	}
	
	public function __get($key) {
	   if ($this->registry->has($key)) {
	       return $this->registry->get($key);
	   } 
	}
	
	public function __set($key, $value) {
		$this->registry->set($key, $value);
	}
}
