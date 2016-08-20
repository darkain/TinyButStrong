<?php


trait tbx_api {

	// Public properties
	public	$Source		= '';
	public	$ErrCount	= 0;
	public	$Version	= '10.0.4';




	public function __toString() {
		return $this->Source;
	}




	public function __invoke($names, $value=false) {
		$start	= 0;
		$names	= explode(',', $names);

		foreach ($names as $name) {
			$name = trim($name);
			switch ($name) {
				case '':		continue;
				case 'onload':	$this->_mergeOn($this->Source, 'onload');	continue;
				case 'onshow':	$this->_mergeOn($this->Source, 'onshow');	continue;
				case 'var':		$this->_mergeAuto($this->Source);			continue;
			}
			$begin = 0;
			while ($part = $this->_find($this->Source, $name, $begin, '.')) {
				$begin = $this->_replace($this->Source, $part, $value, $start);
			}
		}

		return $this;
	}




	public function field($names, $value) {
		return $this($names, $value);
	}




	public function fields($fields) {
		foreach ($fields as $name => $value) {
			$this($name, $value);
		}
		return $this;
	}




	public function block($list, $source, $Query='', $QryPrms=false) {
		if (is_string($list)) $list = explode(',',$list);
		$this->meth_Merge_Block($this->Source, $list, $source, $Query, false, 0, $QryPrms);
		return $this;
	}




	public function repeat($list, $source, $Query='', $QryPrms=false) {
		$this->_P1 = true;
		$this->block($list, $source, $Query, $QryPrms);
		$this->_P1 = false;
		return $this;
	}




	//Custom merger - both field and block supported!
	public function merge($data) {
		if (empty($data)) return $this;

		foreach ($data as $key => $value) {
			if (is_object($value)) {
				throw new tbxException('merge does not support Object data type for key: ' . $key);
			} else if (is_string($value)  ||  is_int($value)  ||  is_float($value)) {
				$this->field($key, $value);
			} else if ($value === false) {
				$this->field($key, []);
			} else if (isset($value[0])  ||  empty($value)) {
				$this->block($key, $value);
			} else {
				$this->field($key, $value);
			}
		}

		return $this;
	}




	//Another merger helper function!
	public function mergePage($filename, $data) {
		return $this->load($filename)->merge($data)->render();
	}




	public function load($file) {
		if (empty($file)) return $this;
		$this->_file($this->Source, $file);
		return $this('var,onload');
	}




	public function loadString($template) {
		$this->Source = $template;
		$this('var,onload');
		return $this;
	}




	public function render($filename=false) {
		if ($filename) $this->load($filename);
		$this('onshow');
		if (!$this->_Mode) echo $this->Source;
		return $this;
	}




	public function renderToString() {
		return (string) $this('onshow');
	}




	public function renderFromString($template) {
		return $this->loadString($template)->render();
	}




	public function renderBlock($filename, $block, $data) {
		return $this->load($filename)->block($block, $data)->render();
	}




	public function renderField($filename, $field, $data) {
		return $this->load($filename)->field($field, $data)->render();
	}




	public function renderString($template) {
		return $this->loadString($template)->renderToString();
	}




	public function fileToString($filename) {
		return $this->load($filename)->renderToString();
	}

}
