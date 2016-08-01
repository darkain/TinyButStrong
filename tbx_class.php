<?php
/*******************************************************************************
*	TinyButStrong - Template Engine for Pro and Beginners                      *
*	------------------------                                                   *
*	Version		: 3.10.0-beta-2015-10-01 for PHP 5                             *
*	Date		: 2015-10-01                                                   *
*	Web site	: http://www.tinybutstrong.com                                 *
*	Author		: http://www.tinybutstrong.com/onlyyou.html                    *
********************************************************************************
*	This library is free software.                                             *
*	You can redistribute and modify it even for commercial usage,              *
*	but you must accept and respect the LPGL License version 3.                *
********************************************************************************
*	TinyButXtreme - Heavily modified fork of TinyButStrong                     *
*	------------------------                                                   *
*	Version		: 10.0.3 for PHP 5.4+ and HHVM 3.6+                            *
*	Date		: 2015-12-19                                                   *
*	Web site	: https://github.com/darkain/TinyButXtreme                     *
*	Author		: Darkain Multimedia                                           *
\******************************************************************************/




// Check PHP version
if (!version_compare(PHP_VERSION,'5.4.0', '>=')) {
	trigger_error(
		'TinyButXtreme Error: PHP 5.4+ or HHVM required, but version '.PHP_VERSION.' detected',
		E_USER_ERROR
	);
}




require_once('tbx_constants.php.inc');
require_once('tbx_locator.php.inc');
require_once('tbx_datasource.php.inc');
require_once('tbx_xml.php.inc');




class clsTinyButXtreme {
	use tbx_xml;

	// Public properties
	public	$Source			= '';
	public	$ErrCount		= 0;

	// Undocumented (can change at any version)
	public	$Version		= '10.0.3';
	public	$filepath		= '';

	// Private
	public	$_Mode			= 0;
	public	$_CurrBlock		= '';
	public	$_P1			= false;



	public function __construct() {
	}




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
		foreach ($data as $key => $value) {
			if (is_object($value)) {
				trigger_error('tbx::merge does not support type Object for key: ' . $key);
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




	protected function _customFormat(&$text, $style) {}




	function _find(&$Txt, $Name, $Pos, $ChrSub) {
	// Find a TBS Locator

		$PosEnd = false;
		$PosMax = strlen($Txt) -1;
		$Start = '['.$Name;

		do {
			// Search for the opening char
			if ($Pos>$PosMax) return false;
			$Pos = strpos($Txt,$Start,$Pos);

			// If found => next chars are analyzed
			if ($Pos===false) return false;

			$Loc = new tbxLocator;
			$ReadPrm = false;
			$PosX = $Pos + strlen($Start);
			$x = $Txt[$PosX];

			if ($x===']') {
				$PosEnd = $PosX;
			} elseif ($x===$ChrSub) {
				$Loc->SubOk = true; // it is no longer the false value
				$ReadPrm = true;
				$PosX++;
			} elseif (strpos(';',$x)!==false) {
				$ReadPrm = true;
				$PosX++;
			} else {
				$Pos++;
			}

			$Loc->PosBeg = $Pos;
			if ($ReadPrm) {
				self::f_Loc_PrmRead($Txt, $PosX, false, '\'', '[', ']', $Loc, $PosEnd);
				if ($PosEnd===false) {
					$this->meth_Misc_Alert('','can\'t found the end of the tag \''.substr($Txt,$Pos,$PosX-$Pos+50).'...\'.');
					$Pos++;
				}
			}
		} while ($PosEnd===false);

		$Loc->PosEnd = $PosEnd;
		if ($Loc->SubOk) {
			$Loc->FullName = $Name.'.'.$Loc->SubName;
			$Loc->SubLst = explode('.',$Loc->SubName);
			$Loc->SubNbr = count($Loc->SubLst);
		} else {
			$Loc->FullName = $Name;
		}
		if ( $ReadPrm && ( isset($Loc->PrmLst['enlarge']) || isset($Loc->PrmLst['comm']) ) ) {
			$Loc->PosBeg0 = $Loc->PosBeg;
			$Loc->PosEnd0 = $Loc->PosEnd;
			$enlarge = (isset($Loc->PrmLst['enlarge'])) ? $Loc->PrmLst['enlarge'] : $Loc->PrmLst['comm'];
			if (($enlarge===true) || ($enlarge==='')) {
				$Loc->Enlarged = self::f_Loc_EnlargeToStr($Txt,$Loc,'<!--' ,'-->');
			} else {
				$Loc->Enlarged = self::f_Loc_EnlargeToTag($Txt,$Loc,$enlarge,false);
			}
		}

		return $Loc;

	}




	function &meth_Locator_SectionNewBDef(&$LocR,$BlockName,$Txt,$PrmLst,$Cache) {

		$Chk	= true;
		$LocLst	= [];
		$LocNbr	= 0;
		$Pos	= 0;

		// Cache TBS locators
		if ($Cache) {
			$Chk = false;
			$PosEndPrec = -1;
			while ($Loc = $this->_find($Txt,$BlockName,$Pos,'.')) {

				// Delete embeding fields
				if ($Loc->PosBeg<$PosEndPrec) {
					unset($LocLst[$LocNbr]);
					$Chk = true;
				}

				$LocNbr = 1 + count($LocLst);
				$LocLst[$LocNbr] = &$Loc;

				// Next search position : always ("original PosBeg" + 1).
				// Must be done here because loc can be moved by the plug-in.
				if ($Loc->Enlarged) {
					// Enlarged
					$Pos = $Loc->PosBeg0 + 1;
					$PosEndPrec = $Loc->PosEnd0;
					$Loc->Enlarged = false;
				} else {
					// Normal
					$Pos = $Loc->PosBeg + 1;
					$PosEndPrec = $Loc->PosEnd;
				}
				if (($Loc->SubName==='#') || ($Loc->SubName==='$')) {
					$Loc->IsRecInfo = true;
					$Loc->RecInfo = $Loc->SubName;
					$Loc->SubName = '';
				} else {
					$Loc->IsRecInfo = false;
				}

				// Process parameter att for new added locators.
				$NewNbr = count($LocLst);
				for ($i=$LocNbr;$i<=$NewNbr;$i++) {
					$li = &$LocLst[$i];
					if (isset($li->PrmLst['att'])) {
						$LocSrc = substr($Txt,$li->PosBeg,$li->PosEnd-$li->PosBeg+1); // for error message
						if ($this->f_Xml_AttFind($Txt, $li, $LocLst, false)) {
							if (isset($Loc->PrmLst['atttrue'])) {
								$li->PrmLst['magnet'] = '#';
								$li->PrmLst['ope'] = (isset($li->PrmLst['ope'])) ? $li->PrmLst['ope'].',attbool' : 'attbool';
							}
							if ($i==$LocNbr) {
								$Pos = $Loc->DelPos;
								$PosEndPrec = -1;
							}
						} else {
							$this->meth_Misc_Alert('','TBS is not able to merge the field '.$LocSrc.' because the entity targeted by parameter \'att\' cannot be found.');
						}
					}
				}
				unset($Loc);

			}

			// Re-order loc
			self::f_Loc_Sort($LocLst, 1);
		}

		// Create the object
		$o = (object) null;
		$o->Prm = $PrmLst;
		$o->LocLst = $LocLst;
		$o->LocNbr = $LocNbr;
		$o->Name = $BlockName;
		$o->Src = $Txt;
		$o->Chk = $Chk;
		$o->IsSerial = false;
		$o->AutoSub = false;
		$i = 1;
		while (isset($PrmLst['sub'.$i])) {
			$o->AutoSub = $i;
			$i++;
		}

		$LocR->BDefLst[] = &$o; // Can be usefull for plug-in
		return $o;

	}




	function meth_Locator_SectionAddGrp(&$LocR,$BlockName,&$BDef,$Type,$Field,$Prm) {

		$BDef->PrevValue = false;
		$BDef->Type = $Type;

		// Save sub items in a structure near to Locator.
		$Field0 = $Field;
		if (strpos($Field,'[')===false) $Field = '['.$BlockName.'.'.$Field.';tbstype='.$Prm.']'; // tbstype is an internal parameter for catching errors
		$BDef->FDef = &$this->meth_Locator_SectionNewBDef($LocR,$BlockName,$Field,[],true);
		if ($BDef->FDef->LocNbr==0)	$this->meth_Misc_Alert('Parameter '.$Prm,'The value \''.$Field0.'\' is unvalide for this parameter.');

		if ($Type==='H') {
			if ($LocR->HeaderFound===false) {
				$LocR->HeaderFound = true;
				$LocR->HeaderNbr = 0;
				$LocR->HeaderDef = []; // 1 to HeaderNbr
			}
			$i = ++$LocR->HeaderNbr;
			$LocR->HeaderDef[$i] = &$BDef;
		} else {
			if ($LocR->FooterFound===false) {
				$LocR->FooterFound = true;
				$LocR->FooterNbr = 0;
				$LocR->FooterDef = []; // 1 to FooterNbr
			}
			$BDef->AddLastGrp = ($Type==='F');
			$i = ++$LocR->FooterNbr;
			$LocR->FooterDef[$i] = &$BDef;
		}

	}




	function _replace(&$Txt, &$Loc, &$Value, $SubStart, $Src=false) {
	// This function enables to merge a locator with a text and returns the position just after the replaced block
	// This position can be useful because we don't know in advance how $Value will be replaced.

		// Found the value if there is a subname
		if (($SubStart!==false) && $Loc->SubOk) {
			for ($i=$SubStart;$i<$Loc->SubNbr;$i++) {
				$x = $Loc->SubLst[$i]; // &$Loc... brings an error with Event Example, I don't know why.
				if (is_array($Value)) {
					if (isset($Value[$x])) {
						$Value = &$Value[$x];
					} elseif (array_key_exists($x,$Value)) {// can happens when value is NULL
						$Value = &$Value[$x];
					} else {
						if (!isset($Loc->PrmLst['noerr'])) {
							$this->meth_Misc_Alert(
								$Loc,
								'item \''.$x.'\' is not an existing key in the array',
								true,
								false,
								$Value
							);
						}
						unset($Value);
						$Value = '';
						break;
					}
				} elseif (is_object($Value)) {

					if (property_exists($Value,$x)) {
						$prop = new ReflectionProperty($Value,$x);
						if ($prop->isStatic()) {
							$x = &$Value::$$x;
						} else {
							$x = &$Value->$x;
						}

					} elseif (isset($Value->$x)) {
						$x = $Value->$x; // useful for overloaded property

					} else {
						if (!isset($Loc->PrmLst['noerr'])) {
							$this->meth_Misc_Alert(
								$Loc,
								'item '.$x.'\' is not a property of class \''.get_class($Value).'\'.',
								true,
								false,
								$Value
							);
						}
						unset($Value);
						$Value = '';
						break;
					}

					$Value = &$x;
					unset($x);
					$x = '';

				} else {
					if (!isset($Loc->PrmLst['noerr'])) $this->meth_Misc_Alert($Loc,'item before \''.$x.'\' is neither an object nor an array. Its type is '.gettype($Value).'.',true);
					unset($Value);
					$Value = '';
					break;
				}
			}
		}

		$CurrVal = $Value; // Unlink

		if (isset($Loc->PrmLst['onformat'])) {
			if ($Loc->FirstMerge) {
				$Loc->OnFrmInfo = $Loc->PrmLst['onformat'];
				$Loc->OnFrmArg = array($Loc->FullName,'',&$Loc->PrmLst,&$this);
			} else {
				$Loc->OnFrmArg[3] = &$this; // bugs.php.net/51174
			}
			$Loc->OnFrmArg[1] = &$CurrVal;
			if (isset($Loc->PrmLst['subtpl'])) {
				$this->meth_Misc_ChangeMode(true,$Loc,$CurrVal);
				call_user_func_array($Loc->OnFrmInfo,$Loc->OnFrmArg);
				$this->meth_Misc_ChangeMode(false,$Loc,$CurrVal);
				$Loc->ConvProtect = false;
				$Loc->ConvStr = false;
			} else {
				call_user_func_array($Loc->OnFrmInfo,$Loc->OnFrmArg);
			}
		}

		if ($Loc->FirstMerge) {
			if (isset($Loc->PrmLst['date'])) {
				$Loc->mode			= TBX_CONVERT_DATE;
				$Loc->ConvProtect	= false;

			} else if (isset($Loc->PrmLst['sprintf'])) {
				$Loc->mode			= TBX_CONVERT_SPRINTF;
				$Loc->ConvProtect	= false;

			} else if (isset($Loc->PrmLst['format'])) {
				$Loc->mode			= TBX_CONVERT_FORMAT;
				$Loc->ConvProtect	= false;

			} else if (isset($Loc->PrmLst['convert'])) {
				$Loc->mode			= TBX_CONVERT_FUNCTION;
				$Loc->ConvProtect	= false;

			} else if (isset($Loc->PrmLst['safe'])) {
				$this->_safe($Loc, $Loc->PrmLst['safe']);

			} else if (isset($Loc->PrmLst['checked'])) {
				$Loc->mode			= TBX_CONVERT_CHECKED;
				$Loc->ConvProtect	= false;

			} else if (isset($Loc->PrmLst['selected'])) {
				$Loc->mode			= TBX_CONVERT_SELECTED;
				$Loc->ConvProtect	= false;

			} else {
				// Analyze parameter 'strconv'
				if (isset($Loc->PrmLst['strconv'])) {
					$this->_safe($Loc, $Loc->PrmLst['strconv']);
				}

				// Analyze parameter 'protect'
				if (isset($Loc->PrmLst['protect'])) {
					$x = strtolower($Loc->PrmLst['protect']);
					if ($x==='no') {
						$Loc->ConvProtect = false;
					} elseif ($x==='yes') {
						$Loc->ConvProtect = true;
					}
				}
			}

			if ($Loc->Ope = isset($Loc->PrmLst['ope'])) {
				$OpeLst = explode(',',$Loc->PrmLst['ope']);
				$Loc->OpeAct = [];
				$Loc->OpeArg = [];
				foreach ($OpeLst as $i=>$ope) {
					switch ($ope) {
						case 'list':
							$Loc->OpeAct[$i] = 1;
							$Loc->OpePrm[$i] = (isset($Loc->PrmLst['valsep'])) ? $Loc->PrmLst['valsep'] : ',';
							if (($Loc->mode === TBX_CONVERT_DEFAULT)  &&  $Loc->ConvStr) {
								$Loc->mode = TBX_CONVERT_UNKNOWN;
							}
						continue;

						case 'minv':
							$Loc->OpeAct[$i] = 11;
							$Loc->MSave = $Loc->MagnetId;
						continue;

						case 'attbool': // this operation key is set when a loc is cached with paremeter atttrue
							$Loc->OpeAct[$i] = 14;
						continue;

						case 'upper':	$Loc->OpeAct[$i] = 15;	continue;
						case 'lower':	$Loc->OpeAct[$i] = 16;	continue;
						case 'upper1':	$Loc->OpeAct[$i] = 17;	continue;
						case 'upperw':	$Loc->OpeAct[$i] = 18;	continue;
						case 'upperx':	$Loc->OpeAct[$i] = 19;	continue;

						default:
							switch (substr($ope,0,4)) {
								case 'max:':
									$Loc->OpeAct[$i] = (isset($Loc->PrmLst['maxhtml'])) ? 2 : 3;
									$Loc->OpePrm[$i] = intval(trim(substr($ope,4)));
									$Loc->OpeEnd = (isset($Loc->PrmLst['maxend'])) ? $Loc->PrmLst['maxend'] : '...';
									if ($Loc->OpePrm[$i]<=0) $Loc->Ope = false;
								continue;

								case 'mod:':	$Loc->OpeAct[$i]	= 5;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'add:':	$Loc->OpeAct[$i]	= 6;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'sub:':	$Loc->OpeAct[$i]	=50;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'mul:':	$Loc->OpeAct[$i]	= 7;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'div:':	$Loc->OpeAct[$i]	= 8;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'mdx:':	$Loc->OpeAct[$i]	=51;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'adx:':	$Loc->OpeAct[$i]	=52;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'sbx:':	$Loc->OpeAct[$i]	=53;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'mlx:':	$Loc->OpeAct[$i]	=54;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'dvx:':	$Loc->OpeAct[$i]	=55;	$Loc->OpePrm[$i]	= '0'+trim(substr($ope,4));	continue;
								case 'mok:':	$Loc->OpeAct[$i]	= 9;	$Loc->OpeMOK[]		= trim(substr($ope,4));	$Loc->MSave = $Loc->MagnetId;	continue;
								case 'mko:':	$Loc->OpeAct[$i]	=10;	$Loc->OpeMKO[]		= trim(substr($ope,4));	$Loc->MSave = $Loc->MagnetId;	continue;
								case 'nif:':	$Loc->OpeAct[$i]	=12;	$Loc->OpePrm[$i]	= trim(substr($ope,4));	continue;
								case 'msk:':	$Loc->OpeAct[$i]	=13;	$Loc->OpePrm[$i]	= trim(substr($ope,4));	continue;
								case 'chk:':	$Loc->OpeAct[$i]	=60;	$Loc->OpePrm[$i]	= trim(substr($ope,4));	continue;
								case 'sel:':	$Loc->OpeAct[$i]	=61;	$Loc->OpePrm[$i]	= trim(substr($ope,4));	continue;
								case 'cus:':	$Loc->OpeAct[$i]	=999999; $Loc->OpePrm[$i]	= trim(substr($ope,4));	continue;

								default:
									if (!isset($Loc->PrmLst['noerr'])) {
										$this->meth_Misc_Alert($Loc,'parameter ope doesn\'t support value \''.$ope.'\'.',true);
									}
								continue;
							}
						continue;
					}
				}
			}
			$Loc->FirstMerge = false;
		}
		$ConvProtect = $Loc->ConvProtect;

		// Operation
		if ($Loc->Ope) {
			foreach ($Loc->OpeAct as $i=>$ope) {
				switch ($ope) {
					case 0:
						$Loc->PrmLst['ope'] = $Loc->OpePrm[$i]; // for compatibility
						$OpeArg		= &$Loc->OpeArg[$i];
						$OpeArg[1]	= &$CurrVal;
						$OpeArg[3]	= &$Txt;
					break;

					case  1:
						if ($Loc->mode===TBX_CONVERT_UNKNOWN) {
							if (is_array($CurrVal)) {
								foreach ($CurrVal as &$v) {
									$v = $this->_string($v);
									$this->_htmlsafe($v, $Loc->break);
								} unset($v);

							} else {
								$CurrVal = $this->_string($CurrVal);
								$this->_htmlsafe($CurrVal, $Loc->break);
							}
						}
						if (is_array($CurrVal)) {
							$CurrVal = implode($Loc->OpePrm[$i], $CurrVal);
						}
					break;

					case  2:
						$x = $this->_string($CurrVal);
						if (strlen($x)>$Loc->OpePrm[$i]) {
							$this->f_Xml_Max($x, $Loc->OpePrm[$i], $Loc->OpeEnd);
						}
					break;

					case  3:
						$x = $this->_string($CurrVal);
						if (strlen($x)>$Loc->OpePrm[$i]) {
							$CurrVal = substr($x, 0, $Loc->OpePrm[$i]).$Loc->OpeEnd;
						}
					break;

					case  5: $CurrVal = ('0'+$CurrVal) % $Loc->OpePrm[$i]; break;
					case  6: $CurrVal = ('0'+$CurrVal) + $Loc->OpePrm[$i]; break;
					case  7: $CurrVal = ('0'+$CurrVal) * $Loc->OpePrm[$i]; break;
					case  8: $CurrVal = ('0'+$CurrVal) / $Loc->OpePrm[$i]; break;
					case  9: case 10:
						if ($ope===9) {
							$CurrVal = (in_array($this->_string($CurrVal),$Loc->OpeMOK)) ? ' ' : '';
						} else {
							$CurrVal = (in_array($this->_string($CurrVal),$Loc->OpeMKO)) ? '' : ' ';
						} // no break here
					case 11:
						if ($this->_string($CurrVal)==='') {
							if ($Loc->MagnetId===0) $Loc->MagnetId = $Loc->MSave;
						} else {
							if ($Loc->MagnetId!==0) {
								$Loc->MSave = $Loc->MagnetId;
								$Loc->MagnetId = 0;
							}
							$CurrVal = '';
						}
						break;
					case 12: if ($this->_string($CurrVal)===$Loc->OpePrm[$i]) $CurrVal = ''; break;
					case 13: $CurrVal = str_replace('*',$CurrVal,$Loc->OpePrm[$i]); break;
					case 14: $CurrVal = self::f_Loc_AttBoolean($CurrVal, $Loc->PrmLst['atttrue'], $Loc->AttName); break;
					case 15: $CurrVal = strtoupper($CurrVal); break;
					case 16: $CurrVal = strtolower($CurrVal); break;
					case 17: $CurrVal = ucfirst($CurrVal); break;
					case 18: $CurrVal = ucwords(strtolower($CurrVal)); break;
					case 19:
						$CurrVal = strtolower($CurrVal);
						$CurrVal = ucwords($CurrVal);
						$CurrVal = str_replace(
							[' A ', ' An ', ' At ', ' In ', ' With ', ' The ', ' And ', ' But ', ' Or ', ' Nor ', ' For ', ' So ', ' Yet ', ' To '],
							[' a ', ' an ', ' at ', ' in ', ' with ', ' the ', ' and ', ' but ', ' or ', ' nor ', ' for ', ' so ', ' yet ', ' to '],
							$CurrVal
						);
					break;

					case 50: $CurrVal = ('0'+$CurrVal) - $Loc->OpePrm[$i]; break;
					case 51: $CurrVal = $Loc->OpePrm[$i] % ('0'+$CurrVal); break;
					case 52: $CurrVal = $Loc->OpePrm[$i] + ('0'+$CurrVal); break;
					case 53: $CurrVal = $Loc->OpePrm[$i] - ('0'+$CurrVal); break;
					case 54: $CurrVal = $Loc->OpePrm[$i] * ('0'+$CurrVal); break;
					case 55: $CurrVal = $Loc->OpePrm[$i] / ('0'+$CurrVal); break;

					case 60: $CurrVal = $Loc->OpePrm[$i] == $CurrVal ? 'checked' : ''; break;
					case 61: $CurrVal = $Loc->OpePrm[$i] == $CurrVal ? 'selected' : ''; break;

					case 999999: $this->_customFormat($CurrVal, $Loc->OpePrm[$i]); break;
				}
			}
		}

		// String conversion or format
		switch ($Loc->mode) {
			case TBX_CONVERT_DEFAULT:
				if ($Loc->ConvHex) {
					$CurrVal = bin2hex($CurrVal);
				} else {
					$CurrVal = $this->_string($CurrVal);
					if ($Loc->ConvPhone) $CurrVal = $this->_phone($CurrVal);
					if ($Loc->ConvStr) $this->_htmlsafe($CurrVal,$Loc->break);
				}
			break;

			case TBX_CONVERT_DATE:
				if (!is_array($CurrVal)	&&
					!is_int($CurrVal)	&&
					!is_float($CurrVal)	&&
					!ctype_digit($CurrVal)) {
					$CurrVal = strtotime($CurrVal);
				}
				$CurrVal = date($Loc->PrmLst['date'], (int)$CurrVal);
			break;

			case TBX_CONVERT_SPRINTF:
				$CurrVal = sprintf($Loc->PrmLst['sprintf'], $this->_string($CurrVal));
			break;

			case TBX_CONVERT_FORMAT:
				$CurrVal = sprintf($Loc->PrmLst['format'], $this->_string($CurrVal));
			break;

			case TBX_CONVERT_SELECTED:
				$CurrVal = trim($Loc->PrmLst['selected']) === $this->_trim($CurrVal) ? 'selected' : '';
			break;

			case TBX_CONVERT_CHECKED:
				$CurrVal = trim($Loc->PrmLst['checked']) === $this->_trim($CurrVal) ? 'checked' : '';
			break;

			case TBX_CONVERT_FUNCTION:
				$list = explode(',', strtolower($Loc->PrmLst['convert']));
				foreach ($list as $item) switch (trim($item)) {
					case 'addslashes':			$CurrVal = addslashes($CurrVal);			break;
					case 'bin2hex':				$CurrVal = bin2hex($CurrVal);				break;
					case 'chr':					$CurrVal = chr($CurrVal);					break;
					case 'chunk_split':			$CurrVal = chunk_split($CurrVal);			break;
					case 'crc32':				$CurrVal = crc32($CurrVal);					break;
					case 'hebrev':				$CurrVal = hebrev($CurrVal);				break;
					case 'hebrevc':				$CurrVal = hebrevc($CurrVal);				break;
					case 'hex':					$CurrVal = bin2hex($CurrVal);				break;
					case 'hex2bin':				$CurrVal = hex2bin($CurrVal);				break;
					case 'html_entity_decode':	$CurrVal = html_entity_decode($CurrVal);	break;
					case 'htmlentities':		$CurrVal = htmlentities($CurrVal);			break;
					case 'htmlspecialchars':	$CurrVal = htmlspecialchars($CurrVal);		break;
					case 'lcfirst':				$CurrVal = lcfirst($CurrVal);				break;
					case 'lower':				$CurrVal = strtolower($CurrVal);			break;
					case 'ltrim':				$CurrVal = ltrim($CurrVal);					break;
					case 'md5':					$CurrVal = md5($CurrVal);					break;
					case 'metaphone':			$CurrVal = metaphone($CurrVal);				break;
					case 'nl2br':				$CurrVal = nl2br($CurrVal);					break;
					case 'number_format':		$CurrVal = number_format($CurrVal);			break;
					case 'ord':					$CurrVal = ord($CurrVal);					break;
					case 'rtrim':				$CurrVal = rtrim($CurrVal);					break;
					case 'sha1':				$CurrVal = sha1($CurrVal);					break;
					case 'soundex':				$CurrVal = soundex($CurrVal);				break;
					case 'strip_tags':			$CurrVal = strip_tags($CurrVal);			break;
					case 'stripcslashes':		$CurrVal = stripcslashes($CurrVal);			break;
					case 'stripslashes':		$CurrVal = stripslashes($CurrVal);			break;
					case 'strlen':				$CurrVal = strlen($CurrVal);				break;
					case 'strrev':				$CurrVal = strrev($CurrVal);				break;
					case 'strtolower':			$CurrVal = strtolower($CurrVal);			break;
					case 'strtoupper':			$CurrVal = strtoupper($CurrVal);			break;
					case 'trim':				$CurrVal = trim($CurrVal);					break;
					case 'ucfirst':				$CurrVal = ucfirst($CurrVal);				break;
					case 'ucwords':				$CurrVal = ucwords($CurrVal);				break;
					case 'unhex':				$CurrVal = hex2bin($CurrVal);				break;
					case 'uudecode':			$CurrVal = convert_uudecode($CurrVal);		break;
					case 'uuencode':			$CurrVal = convert_uuencode($CurrVal);		break;
					case 'upper':				$CurrVal = strtoupper($CurrVal);			break;
					case 'wordwrap':			$CurrVal = wordwrap($CurrVal);				break;
					default: $this->_customFormat($CurrVal, $Loc->PrmLst['convert']);
				}
			break;

			case TBX_CONVERT_SPECIAL:
				if ($Loc->ConvJson) {
					$CurrVal = json_encode(is_object($Src) ? $Src->SrcId : $CurrVal);
					break;
				}

				$CurrVal = $this->_string($CurrVal);

				if ($Loc->ConvStr)			$this->_htmlsafe($CurrVal, $Loc->break);
				if ($Loc->ConvUrl === 1)	$CurrVal = urlencode($CurrVal);
				if ($Loc->ConvUrl === 2)	$CurrVal = strtolower(urlencode(rtrim(substr($CurrVal, 0, 20))));
				if ($Loc->ConvUrl === 3)	$CurrVal = rawurlencode($CurrVal);
				if ($Loc->ConvUrl === 4)	$CurrVal = strtolower(rawurlencode(rtrim(substr($CurrVal, 0, 20))));

				if ($Loc->ConvJS) {
					$CurrVal = str_replace(
						["\n","\r","\t"],
						['\n','\r','\t'],
						addslashes($CurrVal)
					);
				}
			break;
		}

		// if/then/else process, there may be several if/then
		if ($Loc->PrmIfNbr) {
			$z = false;
			$i = 1;
			while ($i!==false) {
				$x = str_replace('[val]',$CurrVal,$Loc->PrmIf[$i]);
				if ($this->f_Misc_CheckCondition($x)) {
					if (isset($Loc->PrmThen[$i])) {
						$z = $Loc->PrmThen[$i];
					}
					$i = false;
				} else {
					$i++;
					if ($i>$Loc->PrmIfNbr) {
						if (isset($Loc->PrmLst['else'])) {
							$z = $Loc->PrmLst['else'];
						}
						$i = false;
					}
				}
			}
			if ($z!==false) {
				if ($ConvProtect) {
					$CurrVal = $this->_protect($CurrVal);
					$ConvProtect = false;
				}
				$CurrVal = str_replace('[val]', $CurrVal, $z);
			}
		}

		if (isset($Loc->PrmLst['file'])) {
			$x = $Loc->PrmLst['file'];
			if ($x===true) $x = $CurrVal;
			$x = trim(str_replace('[val]', $CurrVal, $x));
			$CurrVal = '';
			if ($x!=='') {
				if (!empty($Loc->PrmLst['when'])) {
					if ($this->f_Misc_CheckCondition($Loc->PrmLst['when'])) {
						$this->_file($CurrVal, $x);
						$this->_mergeAuto($CurrVal);
					}
				} else {
					$this->_file($CurrVal, $x);
					$this->_mergeAuto($CurrVal);
				}
				$ConvProtect = false;
			}
		}

		if (isset($Loc->PrmLst['att'])) {
			$this->f_Xml_AttFind($Txt, $Loc, true, false);
			if (isset($Loc->PrmLst['atttrue'])) {
				$CurrVal = self::f_Loc_AttBoolean($CurrVal, $Loc->PrmLst['atttrue'], $Loc->AttName);
				$Loc->PrmLst['magnet'] = '#';
			}
		}

		// Case when it's an empty string
		if ($CurrVal==='') {

			if ($Loc->MagnetId===false) {
				if (isset($Loc->PrmLst['.'])) {
					$Loc->MagnetId = -1;
				} elseif (isset($Loc->PrmLst['ifempty'])) {
					$Loc->MagnetId = -2;
				} elseif (isset($Loc->PrmLst['magnet'])) {
					$Loc->MagnetId = 1;
					$Loc->PosBeg0 = $Loc->PosBeg;
					$Loc->PosEnd0 = $Loc->PosEnd;
					if ($Loc->PrmLst['magnet']==='#') {
						if (!isset($Loc->AttBeg)) {
							$Loc->PrmLst['att'] = '.';
							$this->f_Xml_AttFind($Txt, $Loc, true, false);
						}
						if (isset($Loc->AttBeg)) {
							$Loc->MagnetId = -3;
						} else {
							$this->meth_Misc_Alert($Loc,'parameter \'magnet=#\' cannot be processed because the corresponding attribute is not found.',true);
						}
					} elseif (isset($Loc->PrmLst['mtype'])) {
						switch ($Loc->PrmLst['mtype']) {
						case 'm+m': $Loc->MagnetId = 2; break;
						case 'm*': $Loc->MagnetId = 3; break;
						case '*m': $Loc->MagnetId = 4; break;
						}
					}
				} elseif (isset($Loc->PrmLst['attadd'])) {
					// In order to delete extra space
					$Loc->PosBeg0 = $Loc->PosBeg;
					$Loc->PosEnd0 = $Loc->PosEnd;
					$Loc->MagnetId = 5;
				} else {
					$Loc->MagnetId = 0;
				}
			}

			switch ($Loc->MagnetId) {
				case 0: break;
				case -1: $CurrVal = '&nbsp;'; break; // Enables to avoid null cells in HTML tables
				case -2: $CurrVal = $Loc->PrmLst['ifempty']; break;
				case -3: $Loc->Enlarged = true; $Loc->PosBeg = $Loc->AttBegM; $Loc->PosEnd = $Loc->AttEnd; break;

				case 1:
					$Loc->Enlarged = true;
					$this->f_Loc_EnlargeToTag($Txt,$Loc,$Loc->PrmLst['magnet'],false);
				break;

				case 2:
					$Loc->Enlarged = true;
					$CurrVal = $this->f_Loc_EnlargeToTag($Txt,$Loc,$Loc->PrmLst['magnet'],true);
				break;

				case 3:
					$Loc->Enlarged = true;
					$Loc2 = $this->f_Xml_FindTag($Txt,$Loc->PrmLst['magnet'],true,$Loc->PosBeg,false,false,false);
					if ($Loc2!==false) {
						$Loc->PosBeg = $Loc2->PosBeg;
						if ($Loc->PosEnd<$Loc2->PosEnd) $Loc->PosEnd = $Loc2->PosEnd;
					}
				break;

				case 4:
					$Loc->Enlarged = true;
					$Loc2 = $this->f_Xml_FindTag($Txt,$Loc->PrmLst['magnet'],true,$Loc->PosBeg,true,false,false);
					if ($Loc2!==false) $Loc->PosEnd = $Loc2->PosEnd;
				break;

				case 5:
					$Loc->Enlarged = true;
					if (substr($Txt,$Loc->PosBeg-1,1)==' ') $Loc->PosBeg--;
				break;
			}
			$NewEnd = $Loc->PosBeg; // Useful when mtype='m+m'
		} else {

			if ($ConvProtect) $CurrVal = $this->_protect($CurrVal);
			$NewEnd = $Loc->PosBeg + strlen($CurrVal);

		}

		$Txt = substr_replace($Txt,$CurrVal,$Loc->PosBeg,$Loc->PosEnd-$Loc->PosBeg+1);
		return $NewEnd; // Return the new end position of the field

	}




	function meth_Locator_FindBlockNext(&$Txt,$BlockName,$PosBeg,$ChrSub,$Mode,&$P1,&$FieldBefore) {
	// Return the first block locator just after the PosBeg position
	// Mode = 1 : Merge_Auto => doesn't save $Loc->BlockSrc, save the bounds of TBS Def tags instead, return also fields
	// Mode = 2 : FindBlockLst or GetBlockSource => save $Loc->BlockSrc without TBS Def tags
	// Mode = 3 : GetBlockSource => save $Loc->BlockSrc with TBS Def tags

		$SearchDef = true;
		$FirstField = false;
		// Search for the first tag with parameter "block"
		while ($SearchDef && ($Loc = $this->_find($Txt,$BlockName,$PosBeg,$ChrSub))) {
			if (isset($Loc->PrmLst['block'])) {
				if (isset($Loc->PrmLst['p1'])  ||  $this->_P1) {
					if ($P1) return false;
					$Loc->PrmLst['p1'] = $P1 = true;
				}
				$Block = $Loc->PrmLst['block'];
				$SearchDef = false;
			} elseif ($Mode===1) {
				return $Loc;
			} elseif ($FirstField===false) {
				$FirstField = $Loc;
			}
			$PosBeg = $Loc->PosEnd;
		}

		if ($SearchDef) {
			if ($FirstField!==false) $FieldBefore = true;
			return false;
		}

		$Loc->PosDefBeg = -1;

		if ($Block==='begin') { // Block definied using begin/end

			if (($FirstField!==false) && ($FirstField->PosEnd<$Loc->PosBeg)) $FieldBefore = true;

			$Opened = 1;
			while ($Loc2 = $this->_find($Txt,$BlockName,$PosBeg,$ChrSub)) {
				if (isset($Loc2->PrmLst['block'])) {
					switch ($Loc2->PrmLst['block']) {
					case 'end':   $Opened--; break;
					case 'begin': $Opened++; break;
					}
					if ($Opened==0) {
						if ($Mode===1) {
							$Loc->PosBeg2 = $Loc2->PosBeg;
							$Loc->PosEnd2 = $Loc2->PosEnd;
						} else {
							if ($Mode===2) {
								$Loc->BlockSrc = substr($Txt,$Loc->PosEnd+1,$Loc2->PosBeg-$Loc->PosEnd-1);
							} else {
								$Loc->BlockSrc = substr($Txt,$Loc->PosBeg,$Loc2->PosEnd-$Loc->PosBeg+1);
							}
							$Loc->PosEnd = $Loc2->PosEnd;
						}
						$Loc->BlockFound = true;
						return $Loc;
					}
				}
				$PosBeg = $Loc2->PosEnd;
			}

			return $this->meth_Misc_Alert($Loc,'a least one tag with parameter \'block=end\' is missing.',false,'in block\'s definition');

		}

		if ($Mode===1) {
			$Loc->PosBeg2 = false;
		} else {
			$beg = $Loc->PosBeg;
			$end = $Loc->PosEnd;
			if ($this->f_Loc_EnlargeToTag($Txt,$Loc,$Block,false)===false) return $this->meth_Misc_Alert($Loc,'at least one tag corresponding to &lt;'.$Loc->PrmLst['block'].'&gt; is not found. Check opening tags, closing tags and embedding levels.',false,'in block\'s definition');
			if ($Loc->SubOk || ($Mode===3)) {
				$Loc->BlockSrc = substr($Txt,$Loc->PosBeg,$Loc->PosEnd-$Loc->PosBeg+1);
				$Loc->PosDefBeg = $beg - $Loc->PosBeg;
				$Loc->PosDefEnd = $end - $Loc->PosBeg;
			} else {
				$Loc->BlockSrc = substr($Txt,$Loc->PosBeg,$beg-$Loc->PosBeg).substr($Txt,$end+1,$Loc->PosEnd-$end);
			}
		}

		$Loc->BlockFound = true;
		if (($FirstField!==false) && ($FirstField->PosEnd<$Loc->PosBeg)) $FieldBefore = true;
		return $Loc; // methods return by ref by default

	}




	function meth_Locator_FindBlockLst(&$Txt,$BlockName,$Pos,$SpePrm) {
	// Return a locator object covering all block definitions, even if there is no block definition found.

		$LocR = new tbxLocator;
		$LocR->P1 = false;
		$LocR->FieldOutside = false;
		$LocR->FOStop = false;
		$LocR->BDefLst = [];

		$LocR->NoData = false;
		$LocR->Special = false;
		$LocR->HeaderFound = false;
		$LocR->FooterFound = false;
		$LocR->SerialEmpty = false;

		$LocR->WhenFound = false;
		$LocR->WhenDefault = false;

		$LocR->SectionNbr = 0;       // Normal sections
		$LocR->SectionLst = []; // 1 to SectionNbr

		$BDef = false;
		$ParentLst = [];
		$Pid = 0;

		do {

			if ($BlockName==='') {
				$Loc = false;
			} else {
				$Loc = $this->meth_Locator_FindBlockNext($Txt,$BlockName,$Pos,'.',2,$LocR->P1,$LocR->FieldOutside);
			}

			if ($Loc===false) {

				if ($Pid>0) { // parentgrp mode => disconnect $Txt from the source
					$Parent = &$ParentLst[$Pid];
					$Src = $Txt;
					$Txt = &$Parent->Txt;
					if ($LocR->BlockFound) {
						// Redefine the Header block
						$Parent->Src = substr($Src,0,$LocR->PosBeg);
						// Add a Footer block
						$BDef = &$this->meth_Locator_SectionNewBDef($LocR,$BlockName,substr($Src,$LocR->PosEnd+1),$Parent->Prm,true);
						$this->meth_Locator_SectionAddGrp($LocR,$BlockName,$BDef,'F',$Parent->Fld,'parentgrp');
					}
					// Now go down to previous level
					$Pos = $Parent->Pos;
					$LocR->PosBeg = $Parent->Beg;
					$LocR->PosEnd = $Parent->End;
					$LocR->BlockFound = true;
					unset($Parent);
					unset($ParentLst[$Pid]);
					$Pid--;
					$Loc = true;
				}

			} else {

				$Pos = $Loc->PosEnd;

				// Define the block limits
				if ($LocR->BlockFound) {
					if ( $LocR->PosBeg > $Loc->PosBeg ) $LocR->PosBeg = $Loc->PosBeg;
					if ( $LocR->PosEnd < $Loc->PosEnd ) $LocR->PosEnd = $Loc->PosEnd;
				} else {
					$LocR->BlockFound = true;
					$LocR->PosBeg = $Loc->PosBeg;
					$LocR->PosEnd = $Loc->PosEnd;
				}

				// Merge block parameters
				if (count($Loc->PrmLst)>0) $LocR->PrmLst = array_merge($LocR->PrmLst,$Loc->PrmLst);

				// Force dynamic parameter to be cachable
				if ($Loc->PosDefBeg>=0) {
					$dynprm = array('when','headergrp','footergrp','parentgrp');
					foreach($dynprm as $dp) {
						$n = 0;
						if ((isset($Loc->PrmLst[$dp])) && (strpos($Loc->PrmLst[$dp], '['.$BlockName)!==false)) {
							$n++;
							if ($n==1) {
								$len = $Loc->PosDefEnd - $Loc->PosDefBeg + 1;
								$x = substr($Loc->BlockSrc,$Loc->PosDefBeg,$len);
							}
							$x = str_replace($Loc->PrmLst[$dp],'',$x);
						}
						if ($n>0) $Loc->BlockSrc = substr_replace($Loc->BlockSrc,$x,$Loc->PosDefBeg,$len);
					}
				}
				// Save the block and cache its tags
				$IsParentGrp = isset($Loc->PrmLst['parentgrp']);
				$BDef = &$this->meth_Locator_SectionNewBDef($LocR,$BlockName,$Loc->BlockSrc,$Loc->PrmLst,!$IsParentGrp);

				// Add the text in the list of blocks
				if (isset($Loc->PrmLst['nodata'])) { // Nodata section
					$LocR->NoData = &$BDef;
				} elseif (($SpePrm!==false) && isset($Loc->PrmLst[$SpePrm])) { // Special section (used for navigation bar)
					$LocR->Special = &$BDef;
				} elseif (isset($Loc->PrmLst['when'])) {
					if ($LocR->WhenFound===false) {
						$LocR->WhenFound = true;
						$LocR->WhenSeveral = false;
						$LocR->WhenNbr = 0;
						$LocR->WhenLst = [];
					}
					$BDef->WhenCond = &$this->meth_Locator_SectionNewBDef($LocR,$BlockName,$Loc->PrmLst['when'],[],true);
					$BDef->WhenBeforeNS = ($LocR->SectionNbr===0);
					$i = ++$LocR->WhenNbr;
					$LocR->WhenLst[$i] = &$BDef;
					if (isset($Loc->PrmLst['several'])) $LocR->WhenSeveral = true;
				} elseif (isset($Loc->PrmLst['default'])) {
					$LocR->WhenDefault = &$BDef;
					$LocR->WhenDefaultBeforeNS = ($LocR->SectionNbr===0);
				} elseif (isset($Loc->PrmLst['headergrp'])) {
					$this->meth_Locator_SectionAddGrp($LocR,$BlockName,$BDef,'H',$Loc->PrmLst['headergrp'],'headergrp');
				} elseif (isset($Loc->PrmLst['footergrp'])) {
					$this->meth_Locator_SectionAddGrp($LocR,$BlockName,$BDef,'F',$Loc->PrmLst['footergrp'],'footergrp');
				} elseif (isset($Loc->PrmLst['splittergrp'])) {
					$this->meth_Locator_SectionAddGrp($LocR,$BlockName,$BDef,'S',$Loc->PrmLst['splittergrp'],'splittergrp');
				} elseif ($IsParentGrp) {
					$this->meth_Locator_SectionAddGrp($LocR,$BlockName,$BDef,'H',$Loc->PrmLst['parentgrp'],'parentgrp');
					$BDef->Fld = $Loc->PrmLst['parentgrp'];
					$BDef->Txt = &$Txt;
					$BDef->Pos = $Pos;
					$BDef->Beg = $LocR->PosBeg;
					$BDef->End = $LocR->PosEnd;
					$Pid++;
					$ParentLst[$Pid] = &$BDef;
					$Txt = &$BDef->Src;
					$Pos = $Loc->PosDefBeg + 1;
					$LocR->BlockFound = false;
					$LocR->PosBeg = false;
					$LocR->PosEnd = false;
				} elseif (isset($Loc->PrmLst['serial'])) {
					// Section	with serial subsections
					$SrSrc = &$BDef->Src;
					// Search the empty item
					if ($LocR->SerialEmpty===false) {
						$SrName = $BlockName.'_0';
						$x = false;
						$SrLoc = $this->meth_Locator_FindBlockNext($SrSrc,$SrName,0,'.',2,$x,$x);
						if ($SrLoc!==false) {
							$LocR->SerialEmpty = $SrLoc->BlockSrc;
							$SrSrc = substr_replace($SrSrc,'',$SrLoc->PosBeg,$SrLoc->PosEnd-$SrLoc->PosBeg+1);
						}
					}
					$SrName = $BlockName.'_1';
					$x = false;
					$SrLoc = $this->meth_Locator_FindBlockNext($SrSrc,$SrName,0,'.',2,$x,$x);
					if ($SrLoc!==false) {
						$SrId = 1;
						do {
							// Save previous subsection
							$SrBDef = &$this->meth_Locator_SectionNewBDef($LocR,$SrName,$SrLoc->BlockSrc,$SrLoc->PrmLst,true);
							$SrBDef->SrBeg = $SrLoc->PosBeg;
							$SrBDef->SrLen = $SrLoc->PosEnd - $SrLoc->PosBeg + 1;
							$SrBDef->SrTxt = false;
							$BDef->SrBDefLst[$SrId] = &$SrBDef;
							// Put in order
							$BDef->SrBDefOrdered[$SrId] = &$SrBDef;
							$i = $SrId;
							while (($i>1) && ($SrBDef->SrBeg<$BDef->SrBDefOrdered[$SrId-1]->SrBeg)) {
								$BDef->SrBDefOrdered[$i] = &$BDef->SrBDefOrdered[$i-1];
								$BDef->SrBDefOrdered[$i-1] = &$SrBDef;
								$i--;
							}
							// Search next subsection
							$SrId++;
							$SrName = $BlockName.'_'.$SrId;
							$x = false;
							$SrLoc = $this->meth_Locator_FindBlockNext($SrSrc,$SrName,0,'.',2,$x,$x);
						} while ($SrLoc!==false);
						$BDef->SrBDefNbr = $SrId-1;
						$BDef->IsSerial = true;
						$i = ++$LocR->SectionNbr;
						$LocR->SectionLst[$i] = &$BDef;
					}
				} else {
					// Normal section
					$i = ++$LocR->SectionNbr;
					$LocR->SectionLst[$i] = &$BDef;
				}

			}

		} while ($Loc!==false);

		if ($LocR->WhenFound && ($LocR->SectionNbr===0)) {
			// Add a blank section if When is used without a normal section
			$BDef = &$this->meth_Locator_SectionNewBDef($LocR,$BlockName,'',[],false);
			$LocR->SectionNbr = 1;
			$LocR->SectionLst[1] = &$BDef;
		}

		return $LocR; // methods return by ref by default

	}




	function meth_Merge_Block(&$Txt,$BlockLst,&$SrcId,&$Query,$SpePrm,$SpeRecNum,$QryPrms=false) {

		$BlockSave = $this->_CurrBlock;
		$this->_CurrBlock = $BlockLst;

		// Get source type and info
		$Src = new tbxDatasource;
		if (!$Src->DataPrepare($SrcId, $this)) {
			$this->_CurrBlock = $BlockSave;
			return 0;
		}

		if (is_string($BlockLst)) $BlockLst = explode(',', $BlockLst);
		$BlockNbr = count($BlockLst);
		$BlockId = 0;
		$WasP1 = false;
		$NbrRecTot = 0;
		$QueryZ = &$Query;
		$ReturnData = false;

		while ($BlockId<$BlockNbr) {

			$RecSpe = 0;  // Row with a special block's definition (used for the navigation bar)
			$QueryOk = true;
			$this->_CurrBlock = trim($BlockLst[$BlockId]);
			if ($this->_CurrBlock==='*') {
				$ReturnData = true;
				if ($Src->RecSaved===false) $Src->RecSaving = true;
				$this->_CurrBlock = '';
			}

			// Search the block
			$LocR = $this->meth_Locator_FindBlockLst($Txt,$this->_CurrBlock,0,$SpePrm);

			if ($LocR->BlockFound) {

				if ($LocR->Special!==false) $RecSpe = $SpeRecNum;
				// OnData
				if ($Src->OnDataPrm = isset($LocR->PrmLst['ondata'])) {
					$Src->OnDataPrmRef = $LocR->PrmLst['ondata'];
					if (isset($Src->OnDataPrmDone[$Src->OnDataPrmRef])) {
						$Src->OnDataPrm = false;
					} else {
						$LocR->FullName = $this->_CurrBlock;
						$Src->OnDataPrm = $this->meth_Misc_Alert($LocR,'(parameter ondata) ',false,'block');
					}
				}
				// Dynamic query
				if ($LocR->P1) {
					if ( ($LocR->PrmLst['p1']===true) && ((!is_string($Query)) || (strpos($Query,'%p1%')===false)) ) { // p1 with no value is a trick to perform new block with same name
						if ($Src->RecSaved===false) $Src->RecSaving = true;
					} elseif (is_string($Query)) {
						$Src->RecSaved = false;
						unset($QueryZ); $QueryZ = ''.$Query;
						$i = 1;
						do {
							$x = 'p'.$i;
							if (isset($LocR->PrmLst[$x])) {
								$QueryZ = str_replace('%p'.$i.'%',$LocR->PrmLst[$x],$QueryZ);
								$i++;
							} else {
								$i = false;
							}
						} while ($i!==false);
					}
					$WasP1 = true;
				} elseif (($Src->RecSaved===false) && ($BlockNbr-$BlockId>1)) {
					$Src->RecSaving = true;
				}
			} elseif ($WasP1) {
				$QueryOk = false;
				$WasP1 = false;
			}

			// Open the recordset
			if ($QueryOk) {
				if ((!$LocR->BlockFound) && (!$LocR->FieldOutside)) {
					// Special case: return data without any block to merge
					$QueryOk = false;
					if ($ReturnData && (!$Src->RecSaved)) {
						if ($Src->DataOpen($QueryZ,$QryPrms)) {
							do {$Src->DataFetch();} while ($Src->CurrRec!==false);
							$Src->DataClose();
						}
					}
				}	else {
					$QueryOk = $Src->DataOpen($QueryZ,$QryPrms);
					if (!$QueryOk) {
						if ($WasP1) {	$WasP1 = false;} else {$LocR->FieldOutside = false;} // prevent from infinit loop
					}
				}
			}

			// Merge sections
			if ($QueryOk) {
				if ($Src->Type===2) { // Special for Text merge
					if ($LocR->BlockFound) {
						$Txt = substr_replace($Txt,$Src->RecSet,$LocR->PosBeg,$LocR->PosEnd-$LocR->PosBeg+1);
						$Src->DataFetch(); // store data, may be needed for multiple blocks
						$Src->RecNum = 1;
						$Src->CurrRec = false;
					} else {
						$Src->DataAlert('can\'t merge the block with a text value because the block definition is not found.');
					}
				} elseif ($LocR->BlockFound===false) {
					$Src->DataFetch(); // Merge first record only
				} else {
					$this->meth_Merge_BlockSections($Txt,$LocR,$Src,$RecSpe);
				}
				$Src->DataClose(); // Close the resource
			}

			if (!$WasP1) {
				$NbrRecTot += $Src->RecNum;
				$BlockId++;
			}
			if ($LocR->FieldOutside) $this->_outside($Txt, $Src, $LocR->FOStop);

		}

		// End of the merge
		unset($LocR);
		$this->_CurrBlock = $BlockSave;
		if ($ReturnData) {
			return $Src->RecSet;
		} else {
			unset($Src);
			return $NbrRecTot;
		}

	}




	function meth_Merge_BlockSections(&$Txt,&$LocR,&$Src,&$RecSpe) {

		// Initialise
		$SecId = 0;
		$SecOk = ($LocR->SectionNbr>0);
		$SecSrc = '';
		$BlockRes = []; // The result of the chained merged blocks
		$IsSerial = false;
		$SrId = 0;
		$SrNbr = 0;
		$GrpFound = false;
		if ($LocR->HeaderFound || $LocR->FooterFound) {
			$GrpFound = true;
			if ($LocR->FooterFound) $Src->PrevRec = (object) null;
		}

		// Main loop
		$Src->DataFetch();

		while($Src->CurrRec!==false) {

			// Headers and Footers
			if ($GrpFound) {
				$brk_any = false;
				$brk_src = '';
				if ($LocR->FooterFound) {
					$brk = false;
					for ($i=$LocR->FooterNbr;$i>=1;$i--) {
						$GrpDef = &$LocR->FooterDef[$i];
						$x = $this->meth_Merge_SectionNormal($GrpDef->FDef,$Src);
						if ($Src->RecNum===1) {
							$GrpDef->PrevValue = $x;
							$brk_i = false;
						} else {
							if ($GrpDef->AddLastGrp) {
								$brk_i = &$brk;
							} else {
								unset($brk_i); $brk_i = false;
							}
							if (!$brk_i) $brk_i = !($GrpDef->PrevValue===$x);
							if ($brk_i) {
								$brk_any = true;
								$brk_src = $this->meth_Merge_SectionNormal($GrpDef,$Src->PrevRec).$brk_src;
								$GrpDef->PrevValue = $x;
							}
						}
					}
					$Src->PrevRec->CurrRec = $Src->CurrRec;
					$Src->PrevRec->RecNum = $Src->RecNum;
					$Src->PrevRec->RecKey = $Src->RecKey;
				}
				if ($LocR->HeaderFound) {
					$brk = ($Src->RecNum===1);
					for ($i=1;$i<=$LocR->HeaderNbr;$i++) {
						$GrpDef = &$LocR->HeaderDef[$i];
						$x = $this->meth_Merge_SectionNormal($GrpDef->FDef,$Src);
						if (!$brk) $brk = !($GrpDef->PrevValue===$x);
						if ($brk) {
							$brk_src .= $this->meth_Merge_SectionNormal($GrpDef,$Src);
							$GrpDef->PrevValue = $x;
						}
					}
					$brk_any = ($brk_any || $brk);
				}
				if ($brk_any) {
					if ($IsSerial) {
						$BlockRes[] = $this->meth_Merge_SectionSerial($SecDef,$SrId,$LocR);
						$IsSerial = false;
					}
					$BlockRes[] = $brk_src;
				}
			} // end of header and footer

			// Increment Section
			if (($IsSerial===false) && $SecOk) {
				$SecId++;
				if ($SecId>$LocR->SectionNbr) $SecId = 1;
				$SecDef = &$LocR->SectionLst[$SecId];
				$IsSerial = $SecDef->IsSerial;
				if ($IsSerial) {
					$SrId = 0;
					$SrNbr = $SecDef->SrBDefNbr;
				}
			}

			// Serial Mode Activation
			if ($IsSerial) { // Serial Merge
				$SrId++;
				$SrBDef = &$SecDef->SrBDefLst[$SrId];
				$SrBDef->SrTxt = $this->meth_Merge_SectionNormal($SrBDef,$Src);
				if ($SrId>=$SrNbr) {
					$SecSrc = $this->meth_Merge_SectionSerial($SecDef,$SrId,$LocR);
					$BlockRes[] = $SecSrc;
					$IsSerial = false;
				}
			} else { // Classic merge
				if ($SecOk) {
					if ($Src->RecNum===$RecSpe) $SecDef = &$LocR->Special;
					$SecSrc = $this->meth_Merge_SectionNormal($SecDef,$Src);
				} else {
					$SecSrc = '';
				}
				if ($LocR->WhenFound) { // With conditional blocks
					$found = false;
					$continue = true;
					$i = 1;
					do {
						$WhenBDef = &$LocR->WhenLst[$i];
						$cond = $this->meth_Merge_SectionNormal($WhenBDef->WhenCond,$Src);
						if ($this->f_Misc_CheckCondition($cond)) {
							$x_when = $this->meth_Merge_SectionNormal($WhenBDef,$Src);
							if ($WhenBDef->WhenBeforeNS) {$SecSrc = $x_when.$SecSrc;} else {$SecSrc = $SecSrc.$x_when;}
							$found = true;
							if ($LocR->WhenSeveral===false) $continue = false;
						}
						$i++;
						if ($i>$LocR->WhenNbr) $continue = false;
					} while ($continue);
					if (($found===false) && ($LocR->WhenDefault!==false)) {
						$x_when = $this->meth_Merge_SectionNormal($LocR->WhenDefault,$Src);
						if ($LocR->WhenDefaultBeforeNS) {$SecSrc = $x_when.$SecSrc;} else {$SecSrc = $SecSrc.$x_when;}
					}
				}
				$BlockRes[] = $SecSrc;
			}

			// Next row
			$Src->DataFetch();

		} //--> while($CurrRec!==false) {

		$SecSrc = '';

		// Serial: merge the extra the sub-blocks
		if ($IsSerial) $SecSrc .= $this->meth_Merge_SectionSerial($SecDef,$SrId,$LocR);

		// Footer
		if ($LocR->FooterFound) {
			if ($Src->RecNum>0) {
				for ($i=1;$i<=$LocR->FooterNbr;$i++) {
					$GrpDef = &$LocR->FooterDef[$i];
					if ($GrpDef->AddLastGrp) {
						$SecSrc .= $this->meth_Merge_SectionNormal($GrpDef,$Src->PrevRec);
					}
				}
			}
		}

		// NoData
		if ($Src->RecNum===0) {
			if ($LocR->NoData!==false) {
				$SecSrc = $LocR->NoData->Src;
			} elseif(isset($LocR->PrmLst['bmagnet'])) {
				$this->f_Loc_EnlargeToTag($Txt,$LocR,$LocR->PrmLst['bmagnet'],false);
			}
		}

		$BlockRes[] = $SecSrc;
		$BlockRes = implode('',$BlockRes);

		// Merge the result
		$Txt = substr_replace($Txt, $BlockRes, $LocR->PosBeg, $LocR->PosEnd-$LocR->PosBeg+1);
		if ($LocR->P1) $LocR->FOStop = $LocR->PosBeg + strlen($BlockRes) -1;

	}




	function _mergeAuto(&$Txt, $variable='var') {
	// Merge automatic fields

		// Then we scann all fields in the model
		$x = '';
		$Pos = 0;
		while ($Loc = $this->_find($Txt, $variable, $Pos, '.')) {
			if ($Loc->SubNbr==0) $Loc->SubLst[0]=''; // In order to force error message

			if ($Loc->SubLst[0]==='') {
				$Pos = $this->_mergeSpecial($Txt,$Loc);

			} elseif ($Loc->SubLst[0][0]==='~') {
				if (isset($Loc->PrmLst['noerr'])) {
					$Pos = $this->_replace($Txt,$Loc,$x,false);
				} else {
					$this->meth_Misc_Alert($Loc,'property is neither an object nor an array.',true);
					$Pos = $Loc->PosEnd + 1;
				}

			} elseif (isset($GLOBALS[$Loc->SubLst[0]])) {
				$Pos = $this->_replace($Txt, $Loc, $GLOBALS[$Loc->SubLst[0]],1);

			} else {
				if (isset($Loc->PrmLst['noerr'])) {
					$Pos = $this->_replace($Txt,$Loc,$x,false);
				} else {
					$Pos = $Loc->PosEnd + 1;
					$this->meth_Misc_Alert($Loc,'the key \''.$Loc->SubLst[0].'\' does not exist',true);
				}
			}
		}

		return false; // Useful for properties PrmIfVar & PrmThenVar
	}




	// Merge Special Var Fields ([var..*])
	protected function _mergeSpecial(&$Txt,&$Loc) {
		$SubStart = false;

		if (!isset($Loc->SubLst[1])) {
			$error = 'missing subname.';
		} else {
			switch ($Loc->SubLst[1]) {
				case 'now':
				case 'time':
					$x = time();
				break;

				case 'microtime':
					$x = microtime(true);
				break;

				case 'version':
					$x = $this->Version;
				break;

				case 'tbx':
					$x = 'TinyButXtreme version '.$this->Version.' for PHP 5.4+ and HHVM 3.6+';
				break;

				default:
					$error = '"'.$Loc->SubLst[1].'" is an unsupported keyword.';
			}
		}

		if (!empty($error)) {
			$this->meth_Misc_Alert($Loc,$error);
			$x = '';
		}

		if ($Loc->PosBeg === false) return $Loc->PosEnd;
		return $this->_replace($Txt,$Loc,$x,$SubStart);
	}




	function _outside(&$Txt, &$Src, $PosMax) {
		$Pos = 0;
		$SubStart = ($Src->CurrRec===false) ? false : 0;
		do {
			$Loc = $this->_find($Txt, $this->_CurrBlock, $Pos, '.');
			if ($Loc === false) return;
			if (($PosMax !== false) && ($Loc->PosEnd > $PosMax)) return;

			if ($Loc->SubName==='#') {
				$NewEnd = $this->_replace($Txt, $Loc, $Src->RecNum, false, $Src);
			} else {
				$NewEnd = $this->_replace($Txt, $Loc, $Src->CurrRec, $SubStart, $Src);
			}

			if ($PosMax !== false) $PosMax += $NewEnd - $Loc->PosEnd;

			$Pos = $NewEnd;

		} while (1);
	}




	function meth_Merge_SectionNormal(&$BDef,&$Src) {

		$Txt = $BDef->Src;
		$LocLst = &$BDef->LocLst;
		$iMax = $BDef->LocNbr;
		$PosMax = strlen($Txt);

		if ($Src===false) { // Erase all fields

			$x = '';

			// Chached locators
			for ($i=$iMax;$i>0;$i--) {
				if ($LocLst[$i]->PosBeg<$PosMax) {
					$this->_replace($Txt,$LocLst[$i],$x,false);
					if ($LocLst[$i]->Enlarged) {
						$PosMax = $LocLst[$i]->PosBeg;
						$LocLst[$i]->PosBeg = $LocLst[$i]->PosBeg0;
						$LocLst[$i]->PosEnd = $LocLst[$i]->PosEnd0;
						$LocLst[$i]->Enlarged = false;
					}
				}
			}

			// Uncached locators
			if ($BDef->Chk) {
				$BlockName = &$BDef->Name;
				$Pos = 0;
				while ($Loc = $this->_find($Txt,$BlockName,$Pos,'.')) $Pos = $this->_replace($Txt,$Loc,$x,false);
			}

		} else {

			// Cached locators
			for ($i=$iMax;$i>0;$i--) {
				if ($LocLst[$i]->PosBeg<$PosMax) {
					if ($LocLst[$i]->IsRecInfo) {
						if ($LocLst[$i]->RecInfo==='#') {
							$this->_replace($Txt,$LocLst[$i],$Src->RecNum,false);
						} else {
							$this->_replace($Txt,$LocLst[$i],$Src->RecKey,false);
						}
					} else {
						$this->_replace($Txt,$LocLst[$i],$Src->CurrRec,0);
					}
					if ($LocLst[$i]->Enlarged) {
						$PosMax = $LocLst[$i]->PosBeg;
						$LocLst[$i]->PosBeg = $LocLst[$i]->PosBeg0;
						$LocLst[$i]->PosEnd = $LocLst[$i]->PosEnd0;
						$LocLst[$i]->Enlarged = false;
					}
				}
			}

			// Unchached locators
			if ($BDef->Chk) {
				$BlockName = &$BDef->Name;
				foreach ($Src->CurrRec as $key => $val) {
					$Pos = 0;
					$Name = $BlockName.'.'.$key;
					while ($Loc = $this->_find($Txt,$Name,$Pos,'.')) $Pos = $this->_replace($Txt,$Loc,$val,0);
				}
				$Pos = 0;
				$Name = $BlockName.'.#';
				while ($Loc = $this->_find($Txt,$Name,$Pos,'.')) $Pos = $this->_replace($Txt,$Loc,$Src->RecNum,0);
				$Pos = 0;
				$Name = $BlockName.'.$';
				while ($Loc = $this->_find($Txt,$Name,$Pos,'.')) $Pos = $this->_replace($Txt,$Loc,$Src->RecKey,0);
			}

		}

		// Automatic sub-blocks
		if (isset($BDef->AutoSub)) {
			for ($i=1;$i<=$BDef->AutoSub;$i++) {
				$name = $BDef->Name.'_sub'.$i;
				$query = '';
				$col = $BDef->Prm['sub'.$i];
				if ($col===true) $col = '';
				$col_opt = (substr($col,0,1)==='(') && (substr($col,-1,1)===')');
				if ($col_opt) $col = substr($col,1,strlen($col)-2);
				if ($col==='') {
					// $col_opt cannot be used here because values which are not array nore object are reformated by $Src into an array with keys 'key' and 'val'
					$data = &$Src->CurrRec;
				} elseif (is_object($Src->CurrRec)) {
					$data = &$Src->CurrRec->$col;
				} else {
					if (array_key_exists($col, $Src->CurrRec)) {
						$data = &$Src->CurrRec[$col];
					} else {
						if (!$col_opt) $this->meth_Misc_Alert('for merging the automatic sub-block ['.$name.']','key \''.$col.'\' is not found in record #'.$Src->RecNum.' of block ['.$BDef->Name.']. This key can become optional if you designate it with parenthesis in the main block, i.e.: sub'.$i.'=('.$col.')');
						unset($data); $data = [];
					}
				}
				if (is_string($data)) {
					$data = explode(',',$data);
				} elseif (is_null($data) || ($data===false)) {
					$data = [];
				}
				$this->meth_Merge_Block($Txt, $name, $data, $query, false, 0, false);
			}
		}

		return $Txt;

	}




	function meth_Merge_SectionSerial(&$BDef,&$SrId,&$LocR) {

		$Txt = $BDef->Src;
		$SrBDefOrdered = &$BDef->SrBDefOrdered;
		$Empty = &$LocR->SerialEmpty;

		// All Items
		$F = false;
		for ($i=$BDef->SrBDefNbr;$i>0;$i--) {
			$SrBDef = &$SrBDefOrdered[$i];
			if ($SrBDef->SrTxt===false) { // Subsection not merged with a record
				if ($Empty===false) {
					$SrBDef->SrTxt = $this->meth_Merge_SectionNormal($SrBDef,$F);
				} else {
					$SrBDef->SrTxt = $Empty;
				}
			}
			$Txt = substr_replace($Txt,$SrBDef->SrTxt,$SrBDef->SrBeg,$SrBDef->SrLen);
			$SrBDef->SrTxt = false;
		}

		$SrId = 0;
		return $Txt;

	}




	// Merge [onload] or [onshow] fields and blocks
	function _mergeOn(&$Txt, $Name) {

		$GrpDisplayed	= [];
		$GrpExclusive	= [];
		$P1				= false;
		$FieldBefore	= false;
		$Pos			= 0;

		while ($LocA=$this->meth_Locator_FindBlockNext($Txt, $Name, $Pos, '_', 1, $P1, $FieldBefore)) {

			if ($LocA->BlockFound) {

				if (!isset($GrpDisplayed[$LocA->SubName])) {
					$GrpDisplayed[$LocA->SubName] = false;
					$GrpExclusive[$LocA->SubName] = ($LocA->SubName!=='');
				}
				$Displayed = &$GrpDisplayed[$LocA->SubName];
				$Exclusive = &$GrpExclusive[$LocA->SubName];

				$DelBlock = false;
				$DelField = false;
				if ($Displayed && $Exclusive) {
					$DelBlock = true;
				} else {
					if (isset($LocA->PrmLst['when'])) {
						if (isset($LocA->PrmLst['several'])) $Exclusive=false;
						$x = $LocA->PrmLst['when'];
						if ($this->f_Misc_CheckCondition($x)) {
							$DelField = true;
							$Displayed = true;
						} else {
							$DelBlock = true;
						}
					} elseif(isset($LocA->PrmLst['default'])) {
						if ($Displayed) {
							$DelBlock = true;
						} else {
							$Displayed = true;
							$DelField = true;
						}
						$Exclusive = true; // No more block displayed for the group after
					}
				}

				// Del parts
				if ($DelField) {
					if ($LocA->PosBeg2!==false) $Txt = substr_replace($Txt,'',$LocA->PosBeg2,$LocA->PosEnd2-$LocA->PosBeg2+1);
					$Txt = substr_replace($Txt,'',$LocA->PosBeg,$LocA->PosEnd-$LocA->PosBeg+1);
					$Pos = $LocA->PosBeg;
				} else {
					if ($LocA->PosBeg2===false) {
						if ($this->f_Loc_EnlargeToTag($Txt,$LocA,$LocA->PrmLst['block'],false)===false) $this->meth_Misc_Alert($LocA,'at least one tag corresponding to &lt;'.$LocA->PrmLst['block'].'&gt; is not found. Check opening tags, closing tags and embedding levels.',false,'in block\'s definition');
					} else {
						$LocA->PosEnd = $LocA->PosEnd2;
					}
					if ($DelBlock) {
						$Txt = substr_replace($Txt,'',$LocA->PosBeg,$LocA->PosEnd-$LocA->PosBeg+1);
					} else {
						// Merge the block as if it was a field
						$x = '';
						$this->_replace($Txt,$LocA,$x,false);
					}
					$Pos = $LocA->PosBeg;
				}

			} else { // Field (has no subname at this point)
				$x = '';
				$Pos = $this->_replace($Txt,$LocA,$x,false);
				$Pos = $LocA->PosBeg;
			}

		}

		// merge other fields (must have subnames)
		$this->_mergeAuto($this->Source, $Name);
	}




	// Convert a value to a string
	static function _string($value) {
		if (is_a($value, 'DateTime')) return $value->format('c');
		return @(string)$value;
	}




	// Clean up a phone number
	static function _phone($value) {
		$phone		=	preg_replace('/[^\d]/', '', $value);

		//USA/CANADA
		if (preg_match('/^\d{10}$/', $phone)) {
			return		substr($phone, 0, 3) . '-'
					.	substr($phone, 3, 3) . '-'
					.	substr($phone, 6, 4);
		}

		//USA/CANADA
		if (preg_match('/^1\d{10}$/', $phone)) {
			return		substr($phone, 1, 3) . '-'
					.	substr($phone, 4, 3) . '-'
					.	substr($phone, 7, 4);
		}

		//CZECH REPUBLIC
		if (preg_match('/^00420\d{9}$/', $phone)) {
			return	'00420 '
					.	substr($phone,  5, 3) . ' '
					.	substr($phone,  8, 3) . ' '
					.	substr($phone, 11, 3);
		}

		//FRANCE
		if (preg_match('/^0033\d{9}$/', $phone)) {
			return	'0033 '
					.	substr($phone,  4, 3) . ' '
					.	substr($phone,  7, 2) . ' '
					.	substr($phone,  9, 2) . ' '
					.	substr($phone, 11, 2);
		}

		return $value;
	}




	// Convert a value to a string and trim it
	static function _trim($value) {
		return trim(self::_string($value));
	}




	// PROTECT TBS LOCATORS
	function _protect($string) {
		return str_replace(
			['[',		']'],
			['&#91;',	'&#93;'],
			$string
		);
	}




	// PREPARE SAFETY
	function _safe(&$part, $safe) {
		$part->ConvStr				= false;

		switch (trim(strtolower($safe))) {
			case 'js':
				$this->_safe_default($part);
				$part->ConvJS		= true;
			break;

			case 'json':
				$this->_safe_default($part);
				$part->ConvJson		= true;
				$part->ConvProtect	= false;
			break;

			case 'url':
				$this->_safe_default($part);
				$part->ConvUrl		= 1;
				$part->ConvProtect	= false;
			break;

			case 'urlid':
				$this->_safe_default($part);
				$part->ConvUrl		= 2;
				$part->ConvProtect	= false;
			break;

			case 'raw':
				$this->_safe_default($part);
				$part->ConvUrl		= 3;
				$part->ConvProtect	= false;
			break;

			case 'rawid':
				$this->_safe_default($part);
				$part->ConvUrl		= 4;
				$part->ConvProtect	= false;
			break;

			case 'hex':
				$part->ConvHex		= true;
			break;

			case 'phone':
				$part->ConvPhone	= true;
			break;

			//HANDLED BY DEFAULT OPTION ABOVE
			case 'no':
			case 'none':
			//	$part->ConvStr		= false;
			break;

			//HANDLED BY DEFAULT CASE BELOW
			//case 'yes':
			//	$part->ConvStr		= true;
			//break;

			case 'nobr':
			case 'pre':
			case 'textarea':
				$part->break		= false;
			//Intentionally falling through case

			default:
				$part->ConvStr		= true;
		}
	}




	function _safe_default(&$part) {
		if ($part->mode === TBX_CONVERT_SPECIAL) return;
		$part->mode					= TBX_CONVERT_SPECIAL;
		$part->ConvJS				= false;
		$part->ConvJson				= false;
		$part->ConvUrl				= false;
	}




	// Escape HTML special characters
	function _htmlsafe(&$value, $break=true) {
		$value = htmlspecialchars($value, ENT_QUOTES);
		if ($break) $value			= nl2br($value);
	}




	// Standard alert message provided by TinyButXtreme, return False is the message is cancelled.
	function meth_Misc_Alert($Src, $Msg, $NoErrMsg=false, $SrcType=false, $data='') {
		$this->ErrCount++;

		if (!is_string($Src)) {
			if ($SrcType===false) $SrcType='in field';
			if (isset($Src->PrmLst['tbstype'])) {
				$Msg = 'Column \''.$Src->SubName.'\' is expected but missing in the current record.';
				$Src = 'Parameter \''.$Src->PrmLst['tbstype'].'='.$Src->SubName.'\'';
				$NoErrMsg = false;
			} else {
				$Src = $SrcType.' ['.$Src->FullName.'...]';
			}
		}

		echo "TinyButXtreme Error $Src: $Msg";

		return false;
	}




	function meth_Misc_ChangeMode($Init, &$Loc, &$CurrVal) {
		if ($Init) {
			// Save contents configuration
			$Loc->SaveSrc	= &$this->Source;
			$Loc->SaveMode	= $this->_Mode;
			unset($this->Source); $this->Source = '';
			$this->_Mode++; // Mode>0 means subtemplate mode
		} else {
			$CurrVal		= $this->Source;
			$this->Source	= &$Loc->SaveSrc;
			$this->_Mode	= $Loc->SaveMode;
		}
	}




	// Simply update an array
	static function f_Misc_UpdateArray(&$array, $numerical, $v, $d) {
		if (!is_array($v)) {
			if (is_null($v)) {
				$array = [];
				return;
			} else {
				$v = array($v=>$d);
			}
		}
		foreach ($v as $p=>$a) {
			if ($numerical===true) { // numerical keys
				if (is_string($p)) {
					// syntax: item => true/false
					$i = array_search($p, $array, true);
					if ($i===false) {
						if (!is_null($a)) $array[] = $p;
					} else {
						if (is_null($a)) array_splice($array, $i, 1);
					}
				} else {
					// syntax: i => item
					$i = array_search($a, $array, true);
					if ($i==false) $array[] = $a;
				}
			} else { // string keys
				if (is_null($a)) {
					unset($array[$p]);
				/*} elseif ($numerical==='frm') {
					self::f_Misc_FormatSave($a, $p);*/
				} else {
					$array[$p] = $a;
				}
			}
		}
	}



	static function f_Misc_CheckCondition($Str) {
	// Check if an expression like "exrp1=expr2" is true or false.

		$StrZ = $Str; // same string but without protected data
		$Max = strlen($Str)-1;
		$p = strpos($Str,'\'');
		if ($Esc=($p!==false)) {
			$In = true;
			for ($p=$p+1;$p<=$Max;$p++) {
				if ($StrZ[$p]==='\'') {
					$In = !$In;
				} elseif ($In) {
					$StrZ[$p] = 'z';
				}
			}
		}

		// Find operator and position
		$Ope = '=';
		$Len = 1;
		$p = strpos($StrZ,$Ope);
		if ($p===false) {
			$Ope = '+';
			$p = strpos($StrZ,$Ope);
			if ($p===false) return false;
			if (($p>0) && ($StrZ[$p-1]==='-')) {
				$Ope = '-+'; $p--; $Len=2;
			} elseif (($p<$Max) && ($StrZ[$p+1]==='-')) {
				$Ope = '+-'; $Len=2;
			} else {
				return false;
			}
		} else {
			if ($p>0) {
				$x = $StrZ[$p-1];
				if ($x==='!') {
					$Ope = '!='; $p--; $Len=2;
				} elseif ($x==='~') {
					$Ope = '~='; $p--; $Len=2;
				} elseif ($p<$Max) {
					$y = $StrZ[$p+1];
					if ($y==='=') {
						$Len=2;
					} elseif (($x==='+') && ($y==='-')) {
						$Ope = '+=-'; $p--; $Len=3;
					} elseif (($x==='-') && ($y==='+')) {
						$Ope = '-=+'; $p--; $Len=3;
					}
				} else {
				}
			}
		}

		// Read values
		$Val1  = trim(substr($Str,0,$p));
		$Val2  = trim(substr($Str,$p+$Len));
		if ($Esc) {
			$Nude1 = self::f_Misc_DelDelimiter($Val1,'\'');
			$Nude2 = self::f_Misc_DelDelimiter($Val2,'\'');
		} else {
			$Nude1 = $Nude2 = false;
		}

		// Compare values
		if ($Ope==='=')		return (strcasecmp($Val1, $Val2)==0);
		if ($Ope==='!=')	return (strcasecmp($Val1, $Val2)!=0);
		if ($Ope==='~=')	return (preg_match($Val2, $Val1) >0);

		if ($Nude1) $Val1='0'+$Val1;
		if ($Nude2) $Val2='0'+$Val2;

		if ($Ope==='+-')	return ($Val1  > $Val2);
		if ($Ope==='-+')	return ($Val1  < $Val2);
		if ($Ope==='+=-')	return ($Val1 >= $Val2);
		if ($Ope==='-=+')	return ($Val1 <= $Val2);
		return false;
	}




	static function f_Misc_DelDelimiter(&$Txt,$Delim) {
	// Delete the string delimiters
		$len = strlen($Txt);
		if (($len>1) && ($Txt[0]===$Delim)) {
			if ($Txt[$len-1]===$Delim) $Txt = substr($Txt,1,$len-2);
			return false;
		} else {
			return true;
		}
	}




	protected function _file(&$data, &$file) {
		$data = @file_get_contents($file, true);

		if ($data !== false) {
			$this->filepath = $file;
		} else {
			$path = dirname($this->filepath) . '/';
			$data = @file_get_contents($path.$file, true);
		}

		if ($data === false) {
			trigger_error('Unable to load template file: '.$file, E_USER_ERROR);
			return false;
		}

		return true;
	}




	static function f_Loc_PrmRead(&$Txt,$Pos,$XmlTag,$DelimChrs,$BegStr,$EndStr,&$Loc,&$PosEnd,$WithPos=false) {

		$BegLen = strlen($BegStr);
		$BegChr = $BegStr[0];
		$BegIs1 = ($BegLen===1);

		$DelimIdx = false;
		$DelimCnt = 0;
		$DelimChr = '';
		$BegCnt = 0;
		$SubName = $Loc->SubOk;

		$Status = 0; // 0: name not started, 1: name started, 2: name ended, 3: equal found, 4: value started
		$PosName = 0;
		$PosNend = 0;
		$PosVal = 0;

		// Variables for checking the loop
		$PosEnd = strpos($Txt,$EndStr,$Pos);
		if ($PosEnd===false) return;
		$Continue = ($Pos<$PosEnd);

		while ($Continue) {

			$Chr = $Txt[$Pos];

			if ($DelimIdx) { // Reading in the string

				if ($Chr===$DelimChr) { // Quote found
					if ($Chr===$Txt[$Pos+1]) { // Double Quote => the string continue with un-double the quote
						$Pos++;
					} else { // Simple Quote => end of string
						$DelimIdx = false;
					}
				}

			} else { // Reading outside the string

				if ($BegCnt===0) {

					// Analyzing parameters
					$CheckChr = false;
					if (($Chr===' ') || ($Chr==="\r") || ($Chr==="\n")) {
						if ($Status===1) {
							if ($SubName && ($XmlTag===false)) {
								// Accept spaces in TBS subname.
							} else {
								$Status = 2;
								$PosNend = $Pos;
							}
						} elseif ($XmlTag && ($Status===4)) {
							self::f_Loc_PrmCompute($Txt,$Loc,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
							$Status = 0;
						}
					} elseif (($XmlTag===false) && ($Chr===';')) {
						self::f_Loc_PrmCompute($Txt,$Loc,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
						$Status = 0;
					} elseif ($Status===4) {
						$CheckChr = true;
					} elseif ($Status===3) {
						$Status = 4;
						$DelimCnt = 0;
						$PosVal = $Pos;
						$CheckChr = true;
					} elseif ($Status===2) {
						if ($Chr==='=') {
							$Status = 3;
						} elseif ($XmlTag) {
							self::f_Loc_PrmCompute($Txt,$Loc,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
							$Status = 1;
							$PosName = $Pos;
							$CheckChr = true;
						} else {
							$Status = 4;
							$DelimCnt = 0;
							$PosVal = $Pos;
							$CheckChr = true;
						}
					} elseif ($Status===1) {
						if ($Chr==='=') {
							$Status = 3;
							$PosNend = $Pos;
						} else {
							$CheckChr = true;
						}
					} else {
						$Status = 1;
						$PosName = $Pos;
						$CheckChr = true;
					}

					if ($CheckChr) {
						$DelimIdx = strpos($DelimChrs,$Chr);
						if ($DelimIdx===false) {
							if ($Chr===$BegChr) {
								if ($BegIs1) {
									$BegCnt++;
								} elseif(substr($Txt,$Pos,$BegLen)===$BegStr) {
									$BegCnt++;
								}
							}
						} else {
							$DelimChr = $DelimChrs[$DelimIdx];
							$DelimCnt++;
							$DelimIdx = true;
						}
					}

				} else {
					if ($Chr===$BegChr) {
						if ($BegIs1) {
							$BegCnt++;
						} elseif(substr($Txt,$Pos,$BegLen)===$BegStr) {
							$BegCnt++;
						}
					}
				}

			}

			// Next char
			$Pos++;

			// We check if it's the end
			if ($Pos===$PosEnd) {
				if ($XmlTag) {
					$Continue = false;
				} elseif ($DelimIdx===false) {
					if ($BegCnt>0) {
						$BegCnt--;
					} else {
						$Continue = false;
					}
				}
				if ($Continue) {
					$PosEnd = strpos($Txt,$EndStr,$PosEnd+1);
					if ($PosEnd===false) return;
				} else {
					if ($XmlTag && ($Txt[$Pos-1]==='/')) $Pos--; // In case last attribute is stuck to "/>"
					self::f_Loc_PrmCompute($Txt,$Loc,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
				}
			}

		}

		$PosEnd = $PosEnd + (strlen($EndStr)-1);

	}




	static function f_Loc_PrmCompute(&$Txt,&$Loc,&$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos) {

		if ($Status===0) {
			$SubName = false;
		} else {
			if ($Status===1) {
				$x = substr($Txt,$PosName,$Pos-$PosName);
			} else {
				$x = substr($Txt,$PosName,$PosNend-$PosName);
			}
			if ($XmlTag) $x = strtolower($x);
			if ($SubName) {
				$Loc->SubName = trim($x);
				$SubName = false;
			} else {
				if ($Status===4) {
					$v = trim(substr($Txt,$PosVal,$Pos-$PosVal));
					if ($DelimCnt===1) { // Delete quotes inside the value
						if ($v[0]===$DelimChr) {
							$len = strlen($v);
							if ($v[$len-1]===$DelimChr) {
								$v = substr($v,1,$len-2);
								$v = str_replace($DelimChr.$DelimChr,$DelimChr,$v);
							}
						}
					}
				} else {
					$v = true;
				}
				if ($x==='if') {
					self::f_Loc_PrmIfThen($Loc,true,$v);
				} elseif ($x==='then') {
					self::f_Loc_PrmIfThen($Loc,false,$v);
				} else {
					$Loc->PrmLst[$x] = $v;
					if ($WithPos) $Loc->PrmPos[$x] = array($PosName,$PosNend,$PosVal,$Pos,$DelimChr,$DelimCnt);
				}
			}
		}

	}




	static function f_Loc_PrmIfThen(&$Loc,$IsIf,$Val) {
		$nbr = &$Loc->PrmIfNbr;
		if ($nbr===false) {
			$nbr = 0;
			$Loc->PrmIf = [];
			$Loc->PrmIfVar = [];
			$Loc->PrmThen = [];
			$Loc->PrmThenVar = [];
			$Loc->PrmElseVar = true;
		}
		if ($IsIf) {
			$nbr++;
			$Loc->PrmIf[$nbr] = $Val;
			$Loc->PrmIfVar[$nbr] = true;
		} else {
			$nbr2 = $nbr;
			if ($nbr2===false) $nbr2 = 1; // Only the first 'then' can be placed before its 'if'. This is for compatibility.
			$Loc->PrmThen[$nbr2] = $Val;
			$Loc->PrmThenVar[$nbr2] = true;
		}
	}




	static function f_Loc_EnlargeToStr(&$Txt,&$Loc,$StrBeg,$StrEnd) {
	/*
	This function enables to enlarge the pos limits of the Locator.
	If the search result is not correct, $PosBeg must not change its value, and $PosEnd must be False.
	This is because of the calling function.
	*/

		// Search for the begining string
		$Pos = $Loc->PosBeg;
		$Ok = false;
		do {
			$Pos = strrpos(substr($Txt,0,$Pos),$StrBeg[0]);
			if ($Pos!==false) {
				if (substr($Txt,$Pos,strlen($StrBeg))===$StrBeg) $Ok = true;
			}
		} while ( (!$Ok) && ($Pos!==false) );

		if ($Ok) {
			$PosEnd = strpos($Txt,$StrEnd,$Loc->PosEnd + 1);
			if ($PosEnd===false) {
				$Ok = false;
			} else {
				$Loc->PosBeg = $Pos;
				$Loc->PosEnd = $PosEnd + strlen($StrEnd) - 1;
			}
		}

		return $Ok;

	}




	static function f_Loc_EnlargeToTag(&$Txt,&$Loc,$TagStr,$RetInnerSrc) {
	//Modify $Loc, return false if tags not found, returns the inner source of tag if $RetInnerSrc=true

		// Analyze string
		$Ref = 0;
		$LevelStop = 0;
		$i = 0;
		$TagLst = [];
		$TagBnd = [];
		while ($TagStr!=='') {
			// get next tag
			$p = strpos($TagStr, '+');
			if ($p===false) {
				$t = $TagStr;
				$TagStr = '';
			} else {
				$t = substr($TagStr,0,$p);
				$TagStr = substr($TagStr,$p+1);
			}
			// Check parentheses, relative position and single tag
			do {
				$t = trim($t);
				$e = strlen($t) - 1; // pos of last char
				if (($e>1) && ($t[0]==='(') && ($t[$e]===')')) {
					if ($Ref===0) $Ref = $i;
					if ($Ref===$i) $LevelStop++;
					$t = substr($t,1,$e-1);
				} else {
					if (($e>=0) && ($t[$e]==='/')) $t = substr($t,0,$e); // for compatibilty
					$e = false;
				}
			} while ($e!==false);

			// Check for multiples
			$p = strpos($t, '*');
			if ($p!==false) {
				$n = intval(substr($t, 0, $p));
				$t = substr($t, $p + 1);
				$n = max($n ,1); // prevent for error: minimum value is 1
				$TagStr = str_repeat($t . '+', $n-1) . $TagStr;
			}

			// Reference
			if (($t==='.') && ($Ref===0)) $Ref = $i;
			// Take off the (!) prefix
			$b = '';
			if (($t!=='') && ($t[0]==='!')) {
				$t = substr($t, 1);
				$b = '!';
			}

			if ($t!==false) {
				$TagLst[$i] = $t; // with prefix ! if specified
				$TagBnd[$i] = ($b==='');
				$i++;
			}
		}

		$TagMax = $i-1;

		// Find tags that embeds the locator
		if ($LevelStop===0) $LevelStop = 1;

		// First tag of reference
		if ($TagLst[$Ref] === '.') {
			$TagO = new tbxLocator;
			$TagO->PosBeg = $Loc->PosBeg;
			$TagO->PosEnd = $Loc->PosEnd;
			$PosBeg = $Loc->PosBeg;
			$PosEnd = $Loc->PosEnd;
		} else {
			$TagO = self::f_Loc_Enlarge_Find($Txt,$TagLst[$Ref],$Loc->PosBeg-1,false,$LevelStop);
			if ($TagO===false) return false;
			$PosBeg = $TagO->PosBeg;
			$LevelStop += -$TagO->RightLevel; // RightLevel=1 only if the tag is single and embeds $Loc, otherwise it is 0
			if ($LevelStop>0) {
				$TagC = self::f_Loc_Enlarge_Find($Txt,$TagLst[$Ref],$Loc->PosEnd+1,true,-$LevelStop);
				if ($TagC==false) return false;
				$PosEnd = $TagC->PosEnd;
				$InnerLim = $TagC->PosBeg;
				if ((!$TagBnd[$Ref]) && ($TagMax==0)) {
					$PosBeg = $TagO->PosEnd + 1;
					$PosEnd = $TagC->PosBeg - 1;
				}
			} else {
				$PosEnd = $TagO->PosEnd;
				$InnerLim = $PosEnd + 1;
			}
		}

		$RetVal = true;
		if ($RetInnerSrc) {
			$RetVal = '';
			if ($Loc->PosBeg>$TagO->PosEnd) {
				$RetVal .= substr($Txt,$TagO->PosEnd+1,min($Loc->PosBeg,$InnerLim)-$TagO->PosEnd-1);
			}
			if ($Loc->PosEnd<$InnerLim) {
				$RetVal .= substr($Txt,max($Loc->PosEnd,$TagO->PosEnd)+1,$InnerLim-max($Loc->PosEnd,$TagO->PosEnd)-1);
			}
		}

		// Other tags forward
		$TagC = true;
		for ($i=$Ref+1;$i<=$TagMax;$i++) {
			$x = $TagLst[$i];
			if (($x!=='') && ($TagC!==false)) {
				$level = ($TagBnd[$i]) ? 0 : 1;
				$TagC = self::f_Loc_Enlarge_Find($Txt,$x,$PosEnd+1,true,$level);
				if ($TagC!==false) {
					$PosEnd = ($TagBnd[$i]) ? $TagC->PosEnd : $TagC->PosBeg -1 ;
				}
			}
		}

		// Other tags backward
		$TagO = true;
		for ($i=$Ref-1;$i>=0;$i--) {
			$x = $TagLst[$i];
			if (($x!=='') && ($TagO!==false)) {
				$level = ($TagBnd[$i]) ? 0 : -1;
				$TagO = self::f_Loc_Enlarge_Find($Txt,$x,$PosBeg-1,false,$level);
				if ($TagO!==false) {
					$PosBeg = ($TagBnd[$i]) ? $TagO->PosBeg : $TagO->PosEnd + 1;
				}
			}
		}

		$Loc->PosBeg = $PosBeg;
		$Loc->PosEnd = $PosEnd;
		return $RetVal;

	}




	static function f_Loc_Enlarge_Find($Txt, $Tag, $Pos, $Forward, $LevelStop) {
		return self::f_Xml_FindTag($Txt,$Tag,(!$Forward),$Pos,$Forward,$LevelStop,false);
	}




	static function f_Loc_AttBoolean($CurrVal, $AttTrue, $AttName) {
	// Return the good value for a boolean attribute
		if ($AttTrue===true) {
			if (self::_string($CurrVal)==='') {
				return '';
			} else {
				return $AttName;
			}
		} elseif (self::_string($CurrVal)===$AttTrue) {
			return $AttName;
		} else {
			return '';
		}
	}




	// Sort the locators in the list. Apply the bubble algorithm.
	static function f_Loc_Sort(&$LocLst, $iFirst = 0) {
		$iEnd = count($LocLst) + $iFirst;
		for ($i = $iFirst+1 ; $i<$iEnd ; $i++) {
			$Loc = $LocLst[$i];
			$p = $Loc->PosBeg;
			for ($j=$i-1; $j>=$iFirst ; $j--) {
				if ($p < $LocLst[$j]->PosBeg) {
					$LocLst[$j+1] = $LocLst[$j];
					$LocLst[$j] = $Loc;
				} else {
					$j = -1; // quit the loop
				}
			}
		}
		return true;
	}

}
