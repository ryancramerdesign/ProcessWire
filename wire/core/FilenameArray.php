<?php

class FilenameArray implements IteratorAggregate {

	protected $data = array();

	public function add($filename) {
		$this->data[] = $filename; 
		return $this; 
	}
	
	public function prepend($filename) {
		array_unshift($this->data, $filename); 
		return $this; 	
	}

	public function append($filename) {
		return $this->add($filename); 
	}

	public function getIterator() {
		return new ArrayObject($this->data); 
	}

	public function unique() {
		$this->data = array_unique($this->data); 	
		return $this; 
	}

	public function __toString() {
		return print_r($this->data, true); 
	}

}
