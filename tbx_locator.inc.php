<?php

class tbxLocator {

	/** @var tbx */				private $tbx;
	/** @var int|false */		public $PosBeg			= false;
	/** @var int|false */		public $PosEnd			= false;
	/** @var int|false */		public $PosBeg0			= false;
	/** @var int|false */		public $PosEnd0			= false;
	/** @var bool */			public $Enlarged		= false;
	/** @var string|false */	public $FullName		= false;
	/** @var string */			public $SubName			= '';
	/** @var bool */			public $SubOk			= false;
	/** @var array */			public $SubLst			= [];
	/** @var int */				public $SubNbr			= 0;
	/** @var array */			public $PrmLst			= [];
	/** @var bool */			public $PrmIfNbr		= false;
	/** @var int */				public $MagnetId		= TBX_MAGNET_NONE;
	/** @var bool */			public $BlockFound		= false;
	/** @var bool */			public $FirstMerge		= true;
	/** @var bool */			public $ConvProtect		= true;
	/** @var bool */			public $ConvStr			= true;
	/** @var bool */			public $ConvHex			= false;
	/** @var bool */			public $ConvJson		= false;
	/** @var bool */			public $ConvJS			= false;
	/** @var bool */			public $ConvUrl			= false;
	/** @var bool */			public $placeholder		= false;
	/** @var int */				public $mode			= TBX_CONVERT_DEFAULT;
	/** @var bool */			public $break			= true;
	/** @var int */				public $RightLevel		= 0;
	/** @var bool */			public $HeaderFound		= false;
	/** @var bool */			public $FooterFound		= false;
	/** @var array */			public $PrmPos			= [];
	/** @var array */			public $PrmIf			= [];
	/** @var array */			public $PrmThen			= [];
	/** @var int */				public $HeaderNbr		= 0;
	/** @var array */			public $HeaderDef		= [];
	/** @var int */				public $FooterNbr		= 0;
	/** @var array */			public $FooterDef		= [];
	/** @var bool */			public $IsRecInfo		= false;
	/** @var string */			public $RecInfo			= '';

	//TEMPORARY TO SILENCE PHAN ERRORS
	/** @var mixed */			public $Ope;
	/** @var mixed */			public $OpeAct;
	/** @var mixed */			public $OpeArg;
	/** @var mixed */			public $OpePrm;
	/** @var mixed */			public $OpeEnd;
	/** @var mixed */			public $OpeMOK;
	/** @var mixed */			public $OpeMKO;
	/** @var mixed */			public $MSave;
	/** @var mixed */			public $AttName;
	/** @var mixed */			public $AttBeg;
	/** @var mixed */			public $AttBegM;
	/** @var mixed */			public $AttEnd;
	/** @var mixed */			public $InsPos;
	/** @var mixed */			public $InsLen;
	/** @var mixed */			public $DelPos;
	/** @var mixed */			public $DelLen;
	/** @var mixed */			public $PosBeg2;
	/** @var mixed */			public $PosEnd2;
	/** @var mixed */			public $BlockSrc;
	/** @var mixed */			public $P1;
	/** @var mixed */			public $FieldOutside;
	/** @var mixed */			public $FOStop;
	/** @var mixed */			public $BDefLst;
	/** @var mixed */			public $NoData;
	/** @var mixed */			public $Special;
	/** @var mixed */			public $SerialEmpty;
	/** @var mixed */			public $WhenFound;
	/** @var mixed */			public $WhenDefault;
	/** @var mixed */			public $WhenSeveral;
	/** @var mixed */			public $WhenLst;
	/** @var mixed */			public $WhenNbr;
	/** @var mixed */			public $SectionNbr;
	/** @var mixed */			public $SectionLst;
	/** @var mixed */			public $PosDefBeg;
	/** @var mixed */			public $PosDefEnd;
	/** @var mixed */			public $WhenDefaultBeforeNS;




	public function __construct($tbx) {
		$this->tbx = $tbx;
	}



	public function noerr() {
		return  isset($this->PrmLst['noerr'])
			||  isset($this->PrmLst['ifempty'])
			||  isset($this->PrmLst['magnet']);
	}



	public function PrmRead(&$Txt, $Pos, $DelimChrs, $BegStr, $EndStr, &$PosEnd, $WithPos=false) {
		global $TBX_WHITE_SPACE;

		$XmlTag		= ($BegStr === '<');
		$DelimIdx	= false;
		$DelimCnt	= 0;
		$DelimChr	= '';
		$BegCnt		= 0;
		$SubName	= $this->SubOk;

		$Status		= 0; // 0: name not started, 1: name started, 2: name ended, 3: equal found, 4: value started
		$PosName	= 0;
		$PosNend	= 0;
		$PosVal		= 0;

		// Variables for checking the loop
		$PosEnd = strpos($Txt, $EndStr, $Pos);
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
					if (in_array($Chr, $TBX_WHITE_SPACE, true)) {
						if ($Status===1) {
							if ($SubName && ($XmlTag===false)) {
								// Accept spaces in TBX subname.
							} else {
								$Status = 2;
								$PosNend = $Pos;
							}
						} elseif ($XmlTag && ($Status===4)) {
							$this->PrmCompute($Txt,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
							$Status = 0;
						}
					} elseif (($XmlTag===false) && ($Chr===';')) {
						$this->PrmCompute($Txt,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
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
							$this->PrmCompute($Txt,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
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
							if ($Chr===$BegStr) {
								$BegCnt++;
							}
						} else {
							$DelimChr = $DelimChrs[$DelimIdx];
							$DelimCnt++;
							$DelimIdx = true;
						}
					}

				} else {
					if ($Chr===$BegStr) {
						$BegCnt++;
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
					$PosEnd = strpos($Txt, $EndStr, $PosEnd+1);
					if ($PosEnd===false) return;
				} else {
					if ($XmlTag && ($Txt[$Pos-1]==='/')) $Pos--; // In case last attribute is stuck to "/>"
					$this->PrmCompute($Txt,$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos);
				}
			}

		}
	}




	public function PrmCompute(&$Txt,&$SubName,$Status,$XmlTag,$DelimChr,$DelimCnt,$PosName,$PosNend,$PosVal,$Pos,$WithPos) {

		if ($Status===0) {
			$SubName = false;
		} else {
			if ($Status===1) {
				$x = substr($Txt, $PosName, $Pos-$PosName);
			} else {
				$x = substr($Txt, $PosName, $PosNend-$PosName);
			}
			if ($XmlTag) $x = strtolower($x);
			if ($SubName) {
				$this->SubName = trim($x);
				$SubName = false;
			} else {
				if ($Status === 4) {
					$v = trim(substr($Txt,$PosVal,$Pos-$PosVal));
					if ($DelimCnt === 1) { // Delete quotes inside the value
						if ($v[0] === $DelimChr) {
							$len = strlen($v);
							if ($v[$len-1] === $DelimChr) {
								$v = substr($v, 1, $len-2);
								$v = str_replace($DelimChr.$DelimChr,$DelimChr,$v);
							}
						}
					}
				} else {
					$v = true;
				}

				if ($x === 'if') {
					$this->PrmIfThen(true, $v);

				} elseif ($x === 'then') {
					$this->PrmIfThen(false, $v);

				} else {
					$this->PrmLst[$x] = $v;
					if ($WithPos) {
						$this->PrmPos[$x] = [
							$PosName,
							$PosNend,
							$PosVal,
							$Pos,
							$DelimChr,
							$DelimCnt,
						];
					}
				}
			}
		}

	}




	public function PrmIfThen($IsIf, $Val) {
		$nbr = &$this->PrmIfNbr;

		if ($nbr === false) {
			$nbr = 0;
			$this->PrmIf = [];
			$this->PrmThen = [];
		}

		if ($IsIf) {
			$nbr++;
			$this->PrmIf[$nbr] = $Val;

		} else {
			$nbr2 = $nbr;
			if ($nbr2 === false) $nbr2 = 1; // Only the first 'then' can be placed before its 'if'. This is for compatibility.
			$this->PrmThen[$nbr2] = $Val;
		}
	}




	public function EnlargeToStr(&$Txt, $StrBeg, $StrEnd) {
	/*
	This function enables to enlarge the pos limits of the Locator.
	If the search result is not correct, $PosBeg must not change its value, and $PosEnd must be False.
	This is because of the calling function.
	*/

		// Search for the begining string
		$Pos = $this->PosBeg;
		$Ok = false;
		do {
			$Pos = strrpos(substr($Txt,0,$Pos),$StrBeg[0]);
			if ($Pos!==false) {
				if (substr($Txt,$Pos,strlen($StrBeg))===$StrBeg) $Ok = true;
			}
		} while ( (!$Ok) && ($Pos!==false) );

		if ($Ok) {
			$PosEnd = strpos($Txt,$StrEnd,$this->PosEnd + 1);
			if ($PosEnd===false) {
				$Ok = false;
			} else {
				$this->PosBeg = $Pos;
				$this->PosEnd = $PosEnd + strlen($StrEnd) - 1;
			}
		}

		return $Ok;

	}




	public function EnlargeToTag(&$Txt, $TagStr, $RetInnerSrc=false) {
	//return false if tags not found, returns the inner source of tag if $RetInnerSrc=true

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
				$t = substr($t, $p+1);
				$n = max($n ,1); // prevent for error: minimum value is 1
				$TagStr = str_repeat($t . '+', $n-1) . $TagStr;
			}

			// Reference
			if (($t === '.') && ($Ref === 0)) $Ref = $i;
			// Take off the (!) prefix
			$b = '';
			if (($t !== '') && ($t[0] === '!')) {
				$t = substr($t, 1);
				$b = '!';
			}

			if ($t !== false) {
				$TagLst[$i] = $t; // with prefix ! if specified
				$TagBnd[$i] = ($b === '');
				$i++;
			}
		}

		$TagMax = $i-1;

		// Find tags that embeds the locator
		if ($LevelStop === 0) $LevelStop = 1;

		// First tag of reference
		if ($TagLst[$Ref] === '.') {
			$TagO = new tbxLocator($this->tbx);
			$TagO->PosBeg = $this->PosBeg;
			$TagO->PosEnd = $this->PosEnd;
			$PosBeg = $this->PosBeg;
			$PosEnd = $this->PosEnd;
		} else {
			$TagO = $this->Enlarge_Find($Txt,$TagLst[$Ref],$this->PosBeg-1,false,$LevelStop);
			if ($TagO===false) return false;
			$PosBeg = $TagO->PosBeg;
			$LevelStop += -$TagO->RightLevel; // RightLevel=1 only if the tag is single and embeds $this, otherwise it is 0
			if ($LevelStop>0) {
				$TagC = $this->Enlarge_Find($Txt,$TagLst[$Ref],$this->PosEnd+1,true,-$LevelStop);
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
			if ($this->PosBeg>$TagO->PosEnd) {
				$RetVal .= substr(
					$Txt,
					$TagO->PosEnd+1,
					min($this->PosBeg, $InnerLim) - $TagO->PosEnd - 1
				);
			}
			if ($this->PosEnd<$InnerLim) {
				$RetVal .= substr(
					$Txt,
					max($this->PosEnd, $TagO->PosEnd) + 1,
					$InnerLim-max($this->PosEnd,$TagO->PosEnd) - 1
				);
			}
		}

		// Other tags forward
		$TagC = true;
		for ($i=$Ref+1; $i<=$TagMax; $i++) {
			$x = $TagLst[$i];
			if (($x !== '') && ($TagC !== false)) {
				$level = ($TagBnd[$i]) ? 0 : 1;
				$TagC = $this->Enlarge_Find($Txt,$x,$PosEnd+1,true,$level);
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
				$TagO = $this->Enlarge_Find($Txt,$x,$PosBeg-1,false,$level);
				if ($TagO!==false) {
					$PosBeg = ($TagBnd[$i]) ? $TagO->PosBeg : $TagO->PosEnd + 1;
				}
			}
		}

		$this->PosBeg = $PosBeg;
		$this->PosEnd = $PosEnd;
		return $RetVal;

	}




	////////////////////////////////////////////////////////////////////////////
	// Affects the positions of a list of locators regarding to a specific moving locator.
	////////////////////////////////////////////////////////////////////////////
	public function Moving(&$list) {
		foreach ($list as &$part) {
			if ($part === $this) continue;

			if ($part->PosBeg >= $this->InsPos) {
				$part->PosBeg += $this->InsLen;
				$part->PosEnd += $this->InsLen;
			}

			if ($part->PosBeg  > $this->DelPos) {
				$part->PosBeg -= $this->DelLen;
				$part->PosEnd -= $this->DelLen;
			}
		} unset($part);
	}




	public function SectionAddGrp($BlockName, &$BDef, $Type, $Field, $Prm) {

		$BDef->PrevValue = false;
		$BDef->Type = $Type;

		// Save sub items in a structure near to Locator.
		$Field0 = $Field;
		if (strpos($Field,'[')===false) $Field = '['.$BlockName.'.'.$Field.';tbxtype='.$Prm.']'; // tbxtype is an internal parameter for catching errors
		$BDef->FDef = &$this->SectionNewBDef($BlockName, $Field, [], true);

		if ($BDef->FDef->LocNbr == 0) {
			throw new tbxException(
				'Parameter '.$Prm.': The value \''.$Field0.'\' is not valid for this parameter.'
			);
		}

		if ($Type === 'H') {
			if ($this->HeaderFound===false) {
				$this->HeaderFound = true;
				$this->HeaderNbr = 0;
				$this->HeaderDef = []; // 1 to HeaderNbr
			}
			$i = ++$this->HeaderNbr;
			$this->HeaderDef[$i] = &$BDef;
		} else {
			if ($this->FooterFound===false) {
				$this->FooterFound = true;
				$this->FooterNbr = 0;
				$this->FooterDef = []; // 1 to FooterNbr
			}
			$BDef->AddLastGrp = ($Type==='F');
			$i = ++$this->FooterNbr;
			$this->FooterDef[$i] = &$BDef;
		}

	}




	public function &SectionNewBDef($BlockName, $Txt, $PrmLst, $Cache) {

		$Chk	= true;
		$LocLst	= [];
		$LocNbr	= 0;
		$Pos	= 0;

		// Cache TBX locators
		if ($Cache) {
			$Chk = false;
			$PosEndPrec = -1;
			while ($Loc = $this->tbx->_find($Txt,$BlockName,$Pos,'.')) {

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
						if ($this->tbx->f_Xml_AttFind($Txt, $li, $LocLst)) {
							if (isset($Loc->PrmLst['atttrue'])) {
								$li->PrmLst['magnet'] = '#';
								$li->PrmLst['ope'] = (isset($li->PrmLst['ope'])) ? $li->PrmLst['ope'].',attbool' : 'attbool';
							}
						} else {
							throw new tbxException(
								'TBX is not able to merge the field ' .
								$LocSrc .
								' because the entity targeted by parameter \'att\' cannot be found.'
							);
						}
					}
				}
				unset($Loc);

			}

			// Re-order loc
			tbxLocator::Sort($LocLst, 1);
		}

		// Create the object
		$o = new stdClass;
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

		$this->BDefLst[] = &$o; // Can be usefull for plug-in
		return $o;

	}




	public function Enlarge_Find($Txt, $Tag, $Pos, $Forward, $LevelStop) {
		return $this->tbx->f_Xml_FindTag(
			$Txt,
			$Tag,
			!$Forward,
			$Pos,
			$Forward,
			$LevelStop,
			false
		);
	}




	// RETURN THE GOOD VALUE FOR A BOOLEAN ATTRIBUTE
	public static function AttBoolean($CurrVal, $AttTrue, $AttName) {
		if ($AttTrue === true) {
			return (tbx::_string($CurrVal) !== '')
				? $AttName
				: '';
		}

		return (tbx::_string($CurrVal) === $AttTrue)
			? $AttName
			: '';
	}




	// Sort the locators in the list. Apply the bubble algorithm.
	public static function Sort(&$list, $first=0) {
		$last = count($list) + $first;

		for ($i=$first+1 ; $i<$last; $i++) {
			$locator	= $list[$i];
			$begin		= $locator->PosBeg;

			for ($j=$i-1; $j>=$first; $j--) {
				if ($begin < $list[$j]->PosBeg) {
					$list[$j+1]	= $list[$j];
					$list[$j]	= $locator;
				} else {
					$j = -1; // quit the loop
				}
			}
		}

		return true;
	}




	public function __debugInfo() {
		$return = [];
		foreach ($this as $key => $value) {
			if (!is_object($value)) {
				$return[$key] = $value;
			}
		}
		return $return;
	}
}
