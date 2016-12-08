<?php


trait tbx_api {

	// Public properties
	public	$Source		= '';
	public	$ErrCount	= 0;
	public	$Version	= '10.0.5';




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
				case 'onload':	$this->_mergeOn('onload');			continue;
				case 'onshow':	$this->_mergeOn('onshow');			continue;
				case 'var':		$this->_mergeAuto($this->Source);	continue;
			}
			$begin = 0;
			while ($part = $this->_find($this->Source, $name, $begin, '.')) {
				$begin = $this->_replace($this->Source, $part, $value, $start);
			}
		}

		return $this;
	}




	public function reset() {
		$this->Source		= '';
		$this->filepath		= [];
		$this->_Mode		= 0;
		$this->_CurrBlock	= '';
		$this->_P1			= false;
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
			switch (true) {
				case is_int($value):
				case is_null($value):
				case is_float($value):
				case is_string($value):
					$this->field($key, $value);
				break;

				case $value === false:
					$this->block($key, []);
				break;

				case !is_object($value)  &&  isset($value[0]):
				case empty($value):
					$this->block($key, $value);
				break;

				default:
					$this->field($key, $value);
				break;
			}
		}

		return $this;
	}




	//Another merger helper function!
	public function mergePage($filename, $data) {
		return $this->load($filename)->merge($data)->render();
	}




	public function load($file, $onload=true) {
		$this->reset();
		if (empty($file)) return $this;
		$this->_file($this->Source, $file, true);
		return $onload ? $this('var,onload') : $this;
	}




	public function loadString($template, $onload=true) {
		$this->reset();
		$this->Source = $template;
		return $onload ? $this('var,onload') : $this;
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




	//INHERIT THIS FUNCTION TO WRITE YOUR OWN ARRAY PARSER
	//REMEMBER TO CALL UP TO THIS CLASS TO HANDLE DEFAULT PROCESSING TOO
	protected function tbxArray($locator, $value) {
		if (isset($locator->PrmLst['empty'])) {
			return false;
		}


		if (isset($locator->PrmLst['json'])) {
			$locator->mode			= TBX_CONVERT_SPECIAL;
			$locator->ConvJS		= false;
			$locator->ConvJson		= true;
			$locator->ConvUrl		= false;
			$locator->ConvProtect	= false;
			return $value;
		}


		if (isset($locator->PrmLst['serialize'])) {
			return serialize($value);
		}


		if (isset($locator->PrmLst['first'])) {
			while (is_array($value)) $value = reset($value);
			return $value;
		}


		if (isset($locator->PrmLst['last'])) {
			while (is_array($value)) $value = end($value);
			return $value;
		}


		if (isset($locator->PrmLst['implode'])  &&
			!isset($locator->PrmLst['encase'])) {
			if ($locator->PrmLst['implode'] === true) {
				$locator->PrmLst['implode'] = '';
			}
			return implode($locator->PrmLst['implode'], $value);
		}


		if (!isset($locator->PrmLst['encase'])) return false;

		$encase = str_getcsv($locator->PrmLst['encase'], ',', "'");

		if (is_string($locator->PrmLst['encase'])  &&  count($encase) === 1) {
			if (empty($value)) return '';
			return $encase[0] . implode($encase[0], $value) . $encase[0];
		}

		if (count($encase) === 2) {
			$encase[] = isset($locator->PrmLst['implode'])
				? $locator->PrmLst['implode']
				: '';
		}

		if (count($encase) !== 3) {
			throw new tbxLocException($locator, $value,
				'wrong number of parts for encase'
			);
		}

		if (empty($value)) return '';

		$seperator = $encase[1] . $encase[2] . $encase[0];

		return $encase[0] . implode($seperator, $value) . $encase[1];
	}

}
