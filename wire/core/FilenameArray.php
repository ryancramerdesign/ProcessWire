<?php

class FilenameArray implements IteratorAggregate {

	protected $data = array();

	public function add($filename) {
		$key = $this->getKey($filename);
		$this->data[$key] = $filename; 
		return $this; 
	}

	protected function getKey($filename) {
		$pos = strpos($filename, '?'); 
		$key = $pos ? substr($filename, 0, $pos) : $filename;
		return md5($key);
	}
	
	public function prepend($filename) {
		$key = $this->getKey($filename);	
		$data = array($key => $filename); 
		foreach($this->data as $k => $v) {
			if($k == $key) continue; 
			$data[$k] = $v; 
		}
		$this->data = $data; 
		return $this; 	
	}

	public function append($filename) {
		return $this->add($filename); 
	}

	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	/**
	 * @deprecated no longer necessary since the add() function ensures uniqueness
	 * @return FilenameArray
	 */
	public function unique() {
		// no longer necessary since the add() function ensures uniqueness
		// $this->data = array_unique($this->data); 	
		return $this; 
	}

	public function remove($filename) {
		$key = array_search($filename, $this->data); 
		if($key !== false) unset($this->data[$key]); 
		return $this; 
	}

	public function removeAll() {
		$this->data = array();
		return $this; 
	}

	public function __toString() {
		return print_r($this->data, true); 
	}

}
