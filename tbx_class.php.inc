<?php


require_once('tbx_constants.php.inc');
require_once('tbx_datasource.php.inc');
require_once('tbx_locator.php.inc');

//TRAITS (separated out to help organize code)
require_once('tbx_api.php.inc');
require_once('tbx_function.php.inc');
require_once('tbx_interface.php.inc');
require_once('tbx_safe.php.inc');
require_once('tbx_xml.php.inc');




class tbx {
	use tbx_api;
	use tbx_function;
	use tbx_interface;
	use tbx_safe;
	use tbx_xml;


	// Array storing currently loaded TPL files
	public	$filepath		= [];

	// Private
	public	$_Mode			= 0;
	public	$_CurrBlock		= '';
	public	$_P1			= false;



	public function __construct() {}




	function _find(&$Txt, $Name, $Pos, $ChrSub) {
	// Find a TBX Locator

		$PosEnd			= false;
		$PosMax			= strlen($Txt) -1;
		$Start			= '['.$Name;

		do {
			// Search for the opening char
			if ($Pos>$PosMax) return false;
			$Pos		= strpos($Txt, $Start, $Pos);

			// If found => next chars are analyzed
			if ($Pos===false) return false;

			$Loc		= new tbxLocator($this);
			$ReadPrm	= false;
			$PosX		= $Pos + strlen($Start);
			$depth		= 1;

			switch ($Txt[$PosX]) {
				case '[':
					$depth++;
				break;

				case ']':
					$depth--;
					if ($depth === 0) {
						$PosEnd	= $PosX;
					}
				break;

				case ';':
					$ReadPrm	= true;
					$PosX++;
				break;

				case $ChrSub:
					$Loc->SubOk	= true; // it is no longer the false value
					$ReadPrm	= true;
					$PosX++;
				break;

				default:
					$Pos++;
			}

			$Loc->PosBeg = $Pos;
			if ($ReadPrm) {
				$Loc->PrmRead($Txt, $PosX, '\'', '[', ']', $PosEnd);
				if ($PosEnd === false) {
					throw new tbxLocException($Loc,
						'cant find end of tag <'.substr($Txt,$Pos,$PosX-$Pos+100).'>'
					);
					$Pos++;
				}
			}
		} while ($PosEnd === false);

		$Loc->PosEnd = $PosEnd;
		if ($Loc->SubOk) {
			$Loc->FullName		= $Name.'.'.$Loc->SubName;
			$Loc->SubLst		= explode('.',$Loc->SubName);
			$Loc->SubNbr		= count($Loc->SubLst);
		} else {
			$Loc->FullName		= $Name;
		}

		return $Loc;
	}




	function _replace(&$Txt, &$Loc, &$Value, $SubStart=false, $Src=false) {
	// This function enables to merge a locator with a text and returns the position just after the replaced block
	// This position can be useful because we don't know in advance how $Value will be replaced.

		// Found the value if there is a subname
		if ($SubStart!==false  &&  $Loc->SubOk) {
			for ($i=$SubStart;$i<$Loc->SubNbr;$i++) {
				$x = $Loc->SubLst[$i];
				// &$Loc... brings an error with Event Example, I don't know why.

				if (is_array($Value)) {
					if (isset($Value[$x])) {
						$Value = &$Value[$x];

					} elseif (array_key_exists($x,$Value)) {
						// can happens when value is NULL
						$Value = &$Value[$x];

					} else {
						if (!$Loc->noerr()) {
							throw new tbxLocException($Loc, $Value,
								'key not found'
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

					} elseif (is_a($Value, 'af_object_array')  &&  $Value->offsetExists($x, false)) {
						$x = &$Value->$x;

					} else {
						if (!$Loc->noerr()) {
							throw new tbxLocException($Loc, $Value,
								'property not found'
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
					if (!$Loc->noerr()) {
						throw new tbxLocException($Loc,
							'invalid data type: ' . gettype($Value)
						);
					}
					unset($Value);
					$Value = '';
					break;
				}
			}
		}


		//PROCESS THE ARRAY
		if (tbx_array($Value)) {
			$array = $this->tbxArray($Loc, $Value);

			if ($array === false) {
				throw new tbxLocException($Loc, $Value,
					'no processing instructions'
				);
			}

			$Value = $array;
		}


		$CurrVal = $Value; // Unlink


		if ($Loc->FirstMerge) {
			switch (true) {
				case isset($Loc->PrmLst['date']):
					$Loc->mode			= TBX_CONVERT_DATE;
					$Loc->ConvProtect	= false;
				break;

				case isset($Loc->PrmLst['format']):
				case isset($Loc->PrmLst['sprintf']):
					$Loc->mode			= TBX_CONVERT_FORMAT;
					$Loc->ConvProtect	= false;
				break;

				case isset($Loc->PrmLst['function']):
				case isset($Loc->PrmLst['f']):
				case isset($Loc->PrmLst['convert']):
					$Loc->mode			= TBX_CONVERT_FUNCTION;
					$Loc->ConvProtect	= false;
				break;

				case isset($Loc->PrmLst['safe']):
					$this->_safe($Loc, $Loc->PrmLst['safe']);
				break;

				case isset($Loc->PrmLst['checked']):
					$Loc->mode			= TBX_CONVERT_CHECKED;
					$Loc->ConvProtect	= false;
				break;

				case isset($Loc->PrmLst['selected']):
					$Loc->mode			= TBX_CONVERT_SELECTED;
					$Loc->ConvProtect	= false;
				break;

				case isset($Loc->PrmLst['disabled']):
					$Loc->mode			= TBX_CONVERT_DISABLED;
					$Loc->ConvProtect	= false;
				break;

				default:
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
				break;
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

								default:
									if (!$Loc->noerr()) {
										throw new tbxLocException($Loc,
											'ope doesnt support: ' . $ope
										);
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
					case  0:
						$Loc->PrmLst['ope'] = $Loc->OpePrm[$i]; // for compatibility
						$OpeArg		= &$Loc->OpeArg[$i];
						$OpeArg[1]	= &$CurrVal;
						$OpeArg[3]	= &$Txt;
					break;

					case  1:
						if ($Loc->mode === TBX_CONVERT_UNKNOWN) {
							if (tbx_array($CurrVal)) {
								foreach ($CurrVal as &$v) {
									$v = $this->_string($v);
									$this->_htmlsafe($v, $Loc->break);
								} unset($v);

							} else {
								$CurrVal = $this->_string($CurrVal);
								$this->_htmlsafe($CurrVal, $Loc->break);
							}
						}
						if (tbx_array($CurrVal)) {
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
						if ($this->_string($CurrVal) === '') {
							if ($Loc->MagnetId === TBX_MAGNET_ZERO) $Loc->MagnetId = $Loc->MSave;
						} else {
							if ($Loc->MagnetId !== TBX_MAGNET_ZERO) {
								$Loc->MSave = $Loc->MagnetId;
								$Loc->MagnetId = TBX_MAGNET_ZERO;
							}
							$CurrVal = '';
						}
						break;
					case 12: if ($this->_string($CurrVal)===$Loc->OpePrm[$i]) $CurrVal = ''; break;
					case 13: $CurrVal = str_replace('*',$CurrVal,$Loc->OpePrm[$i]); break;
					case 14: $CurrVal = tbxLocator::AttBoolean($CurrVal, $Loc->PrmLst['atttrue'], $Loc->AttName); break;
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
					if ($Loc->ConvPhone) $CurrVal = $this->tbxPhone($CurrVal);
					if ($Loc->ConvStr) $this->_htmlsafe($CurrVal,$Loc->break);
				}
			break;

			case TBX_CONVERT_DATE:
				if (!tbx_array($CurrVal)	&&
					!is_int($CurrVal)		&&
					!is_float($CurrVal)		&&
					!ctype_digit($CurrVal)) {
					$CurrVal = strtotime($CurrVal);
				}
				$CurrVal = date($Loc->PrmLst['date'], (int)$CurrVal);
			break;

			case TBX_CONVERT_FORMAT:
				if (isset($Loc->PrmLst['format'])) {
					$CurrVal = sprintf($Loc->PrmLst['format'], $this->_string($CurrVal));
				} else if (isset($Loc->PrmLst['sprintf'])) {
					$CurrVal = sprintf($Loc->PrmLst['sprintf'], $this->_string($CurrVal));
				} else {
					throw new tbxLocException($Loc, 'invalid format');
				}
			break;

			case TBX_CONVERT_SELECTED:
				$CurrVal = $this->_property($Loc, $CurrVal, 'selected');
			break;

			case TBX_CONVERT_CHECKED:
				$CurrVal = $this->_property($Loc, $CurrVal, 'checked');
			break;

			case TBX_CONVERT_DISABLED:
				$CurrVal = $this->_property($Loc, $CurrVal, 'disabled');
			break;

			case TBX_CONVERT_FUNCTION:
				if (isset($Loc->PrmLst['function'])) {
					$CurrVal = $this->tbxfunction($this->_string($CurrVal), $Loc->PrmLst['function']);
				} else if (isset($Loc->PrmLst['f'])) {
					$CurrVal = $this->tbxfunction($this->_string($CurrVal), $Loc->PrmLst['f']);
				} else if (isset($Loc->PrmLst['convert'])) {
					$CurrVal = $this->tbxfunction($this->_string($CurrVal), $Loc->PrmLst['convert']);
				} else {
					throw new tbxLocException($Loc, 'invalid function');
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
				$CurrVal = tbxLocator::AttBoolean($CurrVal, $Loc->PrmLst['atttrue'], $Loc->AttName);
				$Loc->PrmLst['magnet'] = '#';
			}
		}

		// Case when it's an empty string
		if ($CurrVal === ''  ||  $CurrVal === false  ||  is_null($CurrVal)) {

			if ($Loc->MagnetId === TBX_MAGNET_NONE) {

				if (isset($Loc->PrmLst['.'])) {
					$Loc->MagnetId = TBX_MAGNET_NBSP;

				} elseif (isset($Loc->PrmLst['ifempty'])) {
					$Loc->MagnetId = TBX_MAGNET_IFEMPTY;

				} elseif (isset($Loc->PrmLst['magnet'])) {
					$Loc->MagnetId = TBX_MAGNET_TAG;
					$Loc->PosBeg0 = $Loc->PosBeg;
					$Loc->PosEnd0 = $Loc->PosEnd;

					if ($Loc->PrmLst['magnet'] === '#') {
						if (!isset($Loc->AttBeg)) {
							$Loc->PrmLst['att'] = '.';
							$this->f_Xml_AttFind($Txt, $Loc, true, false);
						}

						if (isset($Loc->AttBeg)) {
							$Loc->MagnetId = TBX_MAGNET_ATTR;
						} else {
							throw new tbxLocException($Loc,
								"attribute not found for 'magnet=#'"
							);
						}

					} elseif (isset($Loc->PrmLst['mtype'])) {
						switch ($Loc->PrmLst['mtype']) {
							case 'm+m':	$Loc->MagnetId = TBX_MAGNET_PLUS;	break;
							case 'm*':	$Loc->MagnetId = TBX_MAGNET_SUFFIX;	break;
							case '*m':	$Loc->MagnetId = TBX_MAGNET_PREFIX;	break;
						}
					}

				} elseif (isset($Loc->PrmLst['attadd'])) {
					// In order to delete extra space
					$Loc->PosBeg0 = $Loc->PosBeg;
					$Loc->PosEnd0 = $Loc->PosEnd;
					$Loc->MagnetId = TBX_MAGNET_ATTADD;

				} else {
					$Loc->MagnetId = TBX_MAGNET_ZERO;
				}

			} else if ($Loc->MagnetId === TBX_MAGNET_ATTR) {
				$Loc->PrmLst['att'] = '.';
				$this->f_Xml_AttFind($Txt, $Loc, false, true);
			}

			switch ($Loc->MagnetId) {
				case TBX_MAGNET_ZERO: break;

				case TBX_MAGNET_NBSP:
					$CurrVal		= '&nbsp;';		// Enables to avoid null cells in HTML tables
				break;

				case TBX_MAGNET_IFEMPTY:
					$CurrVal		= $Loc->PrmLst['ifempty'];
				break;

				case TBX_MAGNET_ATTR:
					$Loc->Enlarged	= true;
					$Loc->PosBeg	= $Loc->AttBegM;
					$Loc->PosEnd	= $Loc->AttEnd;
				break;

				case TBX_MAGNET_TAG:
					$Loc->Enlarged	= true;
					$Loc->EnlargeToTag($Txt, $Loc->PrmLst['magnet']);
				break;

				case TBX_MAGNET_PLUS:
					$Loc->Enlarged	= true;
					$CurrVal = $Loc->EnlargeToTag($Txt, $Loc->PrmLst['magnet'], true);
				break;

				case TBX_MAGNET_SUFFIX:
					$Loc->Enlarged	= true;
					$Loc2 = $this->f_Xml_FindTag($Txt,$Loc->PrmLst['magnet'],true,$Loc->PosBeg,false,false,false);
					if ($Loc2!==false) {
						$Loc->PosBeg = $Loc2->PosBeg;
						if ($Loc->PosEnd<$Loc2->PosEnd) $Loc->PosEnd = $Loc2->PosEnd;
					}
				break;

				case TBX_MAGNET_PREFIX:
					$Loc->Enlarged	= true;
					$Loc2 = $this->f_Xml_FindTag($Txt,$Loc->PrmLst['magnet'],true,$Loc->PosBeg,true,false,false);
					if ($Loc2!==false) $Loc->PosEnd = $Loc2->PosEnd;
				break;

				case TBX_MAGNET_ATTADD:
					$Loc->Enlarged	= true;
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
	// Mode = 1 : Merge_Auto => doesn't save $Loc->BlockSrc, save the bounds of TBX Def tags instead, return also fields
	// Mode = 2 : FindBlockLst or GetBlockSource => save $Loc->BlockSrc without TBX Def tags
	// Mode = 3 : GetBlockSource => save $Loc->BlockSrc with TBX Def tags

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

			throw new tbxLocException($Loc,
				"a least one tag with parameter 'block=end' is missing"
			);
			return;
		}

		if ($Mode===1) {
			$Loc->PosBeg2 = false;
		} else {
			$beg = $Loc->PosBeg;
			$end = $Loc->PosEnd;
			if ($Loc->EnlargeToTag($Txt, $Block)===false) {
				throw new tbxLocException($Loc,
					'<' . $Loc->PrmLst['block'] . '> not found'
				);
				return;
			}
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

		$LocR = new tbxLocator($this);
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
						$BDef = &$LocR->SectionNewBDef($BlockName, substr($Src,$LocR->PosEnd+1), $Parent->Prm, true);
						$LocR->SectionAddGrp($BlockName, $BDef, 'F', $Parent->Fld, 'parentgrp');
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
				$BDef = &$LocR->SectionNewBDef($BlockName, $Loc->BlockSrc, $Loc->PrmLst, !$IsParentGrp);

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
					$BDef->WhenCond = &$LocR->SectionNewBDef($BlockName, $Loc->PrmLst['when'], [], true);
					$BDef->WhenBeforeNS = ($LocR->SectionNbr===0);
					$i = ++$LocR->WhenNbr;
					$LocR->WhenLst[$i] = &$BDef;
					if (isset($Loc->PrmLst['several'])) $LocR->WhenSeveral = true;
				} elseif (isset($Loc->PrmLst['default'])) {
					$LocR->WhenDefault = &$BDef;
					$LocR->WhenDefaultBeforeNS = ($LocR->SectionNbr===0);
				} elseif (isset($Loc->PrmLst['headergrp'])) {
					$LocR->SectionAddGrp($BlockName, $BDef, 'H', $Loc->PrmLst['headergrp'], 'headergrp');
				} elseif (isset($Loc->PrmLst['footergrp'])) {
					$LocR->SectionAddGrp($BlockName, $BDef, 'F', $Loc->PrmLst['footergrp'], 'footergrp');
				} elseif (isset($Loc->PrmLst['splittergrp'])) {
					$LocR->SectionAddGrp($BlockName, $BDef, 'S', $Loc->PrmLst['splittergrp'], 'splittergrp');
				} elseif ($IsParentGrp) {
					$LocR->SectionAddGrp($BlockName, $BDef, 'H', $Loc->PrmLst['parentgrp'], 'parentgrp');
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
							$SrBDef = &$LocR->SectionNewBDef($SrName, $SrLoc->BlockSrc, $SrLoc->PrmLst, true);
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
			$BDef = &$LocR->SectionNewBDef($BlockName, '', [], false);
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
					$Src->OnDataPrm = false;
					if (!isset($Src->OnDataPrmDone[$Src->OnDataPrmRef])) {
						$LocR->FullName = $this->_CurrBlock;
						throw new tbxLocException($LocR, 'parameter ondata error');
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
						$Src->DataAlert('can\'t merge the block with a text value because the block definition is not found');
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
				$LocR->EnlargeToTag($Txt, $LocR->PrmLst['bmagnet']);
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
				if ($Loc->noerr()) {
					$Pos = $this->_replace($Txt,$Loc,$x);
				} else {
					throw new tbxLocException($Loc,
						'property is neither an object nor an array'
					);
					$Pos = $Loc->PosEnd + 1;
				}

			} elseif (isset($GLOBALS[$Loc->SubLst[0]])) {
				$Pos = $this->_replace($Txt, $Loc, $GLOBALS[$Loc->SubLst[0]], 1);

			} else {
				if ($Loc->noerr()) {
					$Pos = $this->_replace($Txt, $Loc, $x);
				} else {
					$Pos = $Loc->PosEnd + 1;
					throw new tbxLocException($Loc,
						"the key '" . $Loc->SubLst[0] . "' does not exist"
					);
				}
			}
		}

		return false; // Useful for properties PrmIfVar & PrmThenVar
	}




	// Merge Special Var Fields ([var..*])
	protected function _mergeSpecial(&$Txt,&$Loc) {
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
			throw new tbxLocException($Loc, $error);
			$x = '';
		}

		if ($Loc->PosBeg === false) return $Loc->PosEnd;
		return $this->_replace($Txt, $Loc, $x);
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
		$Txt		= $BDef->Src;
		$LocLst		= &$BDef->LocLst;
		$PosMax		= strlen($Txt);

		if ($Src===false) { // Erase all fields

			$x = '';

			// Chached locators
			for ($i=$BDef->LocNbr; $i>0; $i--) {
				if ($LocLst[$i]->PosBeg<$PosMax) {
					$this->_replace($Txt,$LocLst[$i],$x);
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
				while ($Loc = $this->_find($Txt,$BlockName,$Pos,'.')) {
					$Pos = $this->_replace($Txt,$Loc,$x);
				}
			}

		} else {

			// Cached locators
			for ($i=$BDef->LocNbr; $i>0; $i--) {
				if ($LocLst[$i]->PosBeg<$PosMax) {
					if ($LocLst[$i]->IsRecInfo  &&  $LocLst[$i]->RecInfo==='#') {
						$this->_replace($Txt, $LocLst[$i], $Src->RecNum);
					} else if ($LocLst[$i]->IsRecInfo) {
						$this->_replace($Txt, $LocLst[$i], $Src->RecKey);
					} else {
						$this->_replace($Txt, $LocLst[$i], $Src->CurrRec, 0);
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
				$Pos = 0;
				while ($Loc = $this->_find($Txt,$BDef->Name.'.#',$Pos,'.')) {
					$Pos = $this->_replace($Txt,$Loc,$Src->RecNum, 0);
				}

				$Pos = 0;
				while ($Loc = $this->_find($Txt,$BDef->Name.'.$',$Pos,'.')) {
					$Pos = $this->_replace($Txt,$Loc,$Src->RecKey, 0);
				}

				while ($Loc = $this->_find($Txt, $BDef->Name, 0, '.')) {
					$this->_replace($Txt, $Loc, $Src->CurrRec, 0);
				}
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
						if (!$col_opt) {
							throw new tbxException(
								'for merging the automatic sub-block ['.$name.']','key \''.$col.'\' is not found in record #'.$Src->RecNum.' of block ['.$BDef->Name.']. This key can become optional if you designate it with parenthesis in the main block, i.e.: sub'.$i.'=('.$col.')'
							);
						}
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
						if ($LocA->EnlargeToTag($Txt, $LocA->PrmLst['block'])===false) {
							throw new tbxLocException($LocA,
								'<' . $LocA->PrmLst['block'] . '> not found'
							);
						}
					} else {
						$LocA->PosEnd = $LocA->PosEnd2;
					}
					if ($DelBlock) {
						$Txt = substr_replace($Txt,'',$LocA->PosBeg,$LocA->PosEnd-$LocA->PosBeg+1);
					} else {
						// Merge the block as if it was a field
						$x = '';
						$this->_replace($Txt,$LocA,$x);
					}
					$Pos = $LocA->PosBeg;
				}

			} else { // Field (has no subname at this point)
				$x = '';
				$Pos = $this->_replace($Txt,$LocA,$x);
				$Pos = $LocA->PosBeg;
			}

		}

		// merge other fields (must have subnames)
		$this->_mergeAuto($this->Source, $Name);
	}




	// Simply update an array
	static function f_Misc_UpdateArray(&$array, $numerical, $v, $d) {
		if (!tbx_array($v)) {
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




	protected function _file(&$data, $file) {
		if (!empty($this->filepath)) {
			$path = dirname(reset($this->filepath)).DIRECTORY_SEPARATOR.$file;
		}

		if (empty($path)  ||  !file_exists($path)) {
			$path = stream_resolve_include_path($file);
		}

		if (!empty($path)) {
			$this->filepath[] = $path;
			$data = @file_get_contents($path);
			if ($data !== false) return true;
		}

		throw new tbxException('Unable to load template file: '.$file);
		return false;
	}




	protected function _property($locator, $value, $type) {
		$property = $locator->PrmLst[$type];

		switch (true) {
			case $property === true  &&  tbx_empty($value):
			break;

			case $property === true:

			case $value === true						&& !tbx_empty($this->_trim($property)):
			case $value === false						&&  tbx_empty($this->_trim($property)):
			case $value === null						&&  tbx_empty($this->_trim($property)):

			case $this->_trim($property) === 'true'		&& !tbx_empty($this->_trim($value)):
			case $this->_trim($property) === 'false'	&&  tbx_empty($this->_trim($value)):
			case $this->_trim($property) === 'null'		&&  tbx_empty($this->_trim($value)):

			case $this->_trim($value) === $this->_trim($property):


			return $type;
		}

		return '';
	}



}
