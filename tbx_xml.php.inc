<?php

trait tbx_xml {



	/**
	 * Prepare all informations to move a locator according to parameter "att".
	 * @param mixed $MoveLocLst true to simple move the loc, or an array of loc to rearrange the list after the move.
	 *              Note: rearrange doest not work with PHP4.
	 */
	function f_Xml_AttFind(&$Txt, &$Loc, $MoveLocLst=false, $AttDelim=false) {
	// att=div#class ; att=((div))#class ; att=+((div))#class

		$Att = $Loc->PrmLst['att'];
		unset($Loc->PrmLst['att']); // prevent from processing the field twice
		$Loc->PrmLst['att;'] = $Att; // for debug

		$p = strrpos($Att,'#');
		if ($p===false) {
			$TagLst = '';
		} else {
			$TagLst = substr($Att,0,$p);
			$Att = substr($Att,$p+1);
		}

		$Forward = (substr($TagLst,0,1)==='+');
		if ($Forward) $TagLst = substr($TagLst,1);
		$TagLst = explode('+',$TagLst);

		$iMax = count($TagLst)-1;
		$WithPrm = false;
		$LocO = &$Loc;
		foreach ($TagLst as $i=>$Tag) {
			$LevelStop = false;
			while ((strlen($Tag)>1) && (substr($Tag,0,1)==='(') && (substr($Tag,-1,1)===')')) {
				if ($LevelStop===false) $LevelStop = 0;
				$LevelStop++;
				$Tag = trim(substr($Tag,1,strlen($Tag)-2));
			}
			if ($i==$iMax) $WithPrm = true;
			$Pos = ($Forward) ? $LocO->PosEnd+1 : $LocO->PosBeg-1;
			unset($LocO);
			$LocO = $this->f_Xml_FindTag($Txt,$Tag,true,$Pos,$Forward,$LevelStop,$WithPrm,$WithPrm);
			if ($LocO===false) return false;
		}

		$Loc->AttForward = $Forward;
		$Loc->AttTagBeg = $LocO->PosBeg;
		$Loc->AttTagEnd = $LocO->PosEnd;
		$Loc->AttDelimChr = false;

		if ($Att==='.') {
			// this indicates that the TBX field is supposed to be inside an attribute's value
			foreach ($LocO->PrmPos as $a=>$p ) {
				if ( ($p[0]<$Loc->PosBeg) && ($Loc->PosEnd<$p[3]) ) $Att = $a;
			}
			if ($Att==='.') return false;
		}

		$Loc->AttName = $Att;

		$AttLC = strtolower($Att);
		if (isset($LocO->PrmLst[$AttLC])) {
			// The attribute is existing
			$p = $LocO->PrmPos[$AttLC];
			$Loc->AttBeg = $p[0];
			$p[3]--; while ($Txt[$p[3]]===' ') $p[3]--; // external end of the attribute, may has an extra spaces
			$Loc->AttEnd = $p[3];
			$Loc->AttDelimCnt = $p[5];
			$Loc->AttDelimChr = $p[4];
			if (($p[1]>$p[0]) && ($p[2]>$p[1])) {
				//$Loc->AttNameEnd =  $p[1];
				$Loc->AttValBeg = $p[2];
			} else { // attribute without value
				//$Loc->AttNameEnd =  $p[3];
				$Loc->AttValBeg = false;
			}
		} else {
			// The attribute is not yet existing
			$Loc->AttDelimCnt = 0;
			$Loc->AttBeg = false;
		}

		// Search for a delimitor
		if (($Loc->AttDelimCnt==0) && (isset($LocO->PrmPos))) {
			foreach ($LocO->PrmPos as $p) {
				if ($p[5]>0) $Loc->AttDelimChr = $p[4];
			}
		}

		if ($MoveLocLst) return $this->f_Xml_AttMove($Txt,$Loc,$AttDelim,$MoveLocLst);

		return true;
	}




	function f_Xml_AttMove(&$Txt, &$Loc, $AttDelim=false, &$MoveLocLst) {

		if ($AttDelim===false) $AttDelim = $Loc->AttDelimChr;
		if ($AttDelim===false) $AttDelim = '"';

		$DelPos = $Loc->PosBeg;
		$DelLen = $Loc->PosEnd - $Loc->PosBeg + 1;
		$Txt = substr_replace($Txt,'',$DelPos,$DelLen); // delete the current locator
		if ($Loc->AttForward) {
			$Loc->AttTagBeg += -$DelLen;
			$Loc->AttTagEnd += -$DelLen;
		} elseif ($Loc->PosBeg<$Loc->AttTagEnd) {
			$Loc->AttTagEnd += -$DelLen;
		}

		$InsPos = false;
		if ($Loc->AttBeg===false) {
			$InsPos = $Loc->AttTagEnd;
			if ($Txt[$InsPos-1]==='/') $InsPos--;
			if ($Txt[$InsPos-1]===' ') $InsPos--;
			$Ins1 = ' '.$Loc->AttName.'='.$AttDelim;
			$Ins2 = $AttDelim;
			$Loc->AttBeg = $InsPos + 1;
			$Loc->AttValBeg = $InsPos + strlen($Ins1) - 1;
		} else {
			if ($Loc->PosEnd<$Loc->AttBeg) $Loc->AttBeg += -$DelLen;
			if ($Loc->PosEnd<$Loc->AttEnd) $Loc->AttEnd += -$DelLen;
			if ($Loc->AttValBeg===false) {
				$InsPos = $Loc->AttEnd+1;
				$Ins1 = '='.$AttDelim;
				$Ins2 = $AttDelim;
				$Loc->AttValBeg = $InsPos+1;
			} elseif (isset($Loc->PrmLst['attadd'])) {
				$InsPos = $Loc->AttEnd;
				$Ins1 = ' ';
				$Ins2 = '';
			} else {
				// value already existing
				if ($Loc->PosEnd<$Loc->AttValBeg) $Loc->AttValBeg += -$DelLen;
				$PosBeg = $Loc->AttValBeg;
				$PosEnd = $Loc->AttEnd;
				if ($Loc->AttDelimCnt>0) {$PosBeg++; $PosEnd--;}
			}
		}

		if ($InsPos===false) {
			$InsLen = 0;
		} else {
			$InsTxt = $Ins1.'[]'.$Ins2;
			$InsLen = strlen($InsTxt);
			$PosBeg = $InsPos + strlen($Ins1);
			$PosEnd = $PosBeg + 1;
			$Txt = substr_replace($Txt,$InsTxt,$InsPos,0);
			$Loc->AttEnd = $InsPos + $InsLen - 1;
			$Loc->AttTagEnd += $InsLen;
		}

		$Loc->PrevPosBeg = $Loc->PosBeg;
		$Loc->PrevPosEnd = $Loc->PosEnd;
		$Loc->PosBeg = $PosBeg;
		$Loc->PosEnd = $PosEnd;
		$Loc->AttBegM = ($Txt[$Loc->AttBeg-1]===' ') ? $Loc->AttBeg-1 : $Loc->AttBeg; // for magnet=#

		// for CacheField
		if (tbx_array($MoveLocLst)) {
			$Loc->InsPos = $InsPos;
			$Loc->InsLen = $InsLen;
			$Loc->DelPos = $DelPos;
			if ($Loc->InsPos < $Loc->DelPos) $Loc->DelPos += $InsLen;
			$Loc->DelLen = $DelLen;
			$Loc->Moving($MoveLocLst);
		}

		return true;
	}




	function f_Xml_Max(&$Txt,&$Nbr,$MaxEnd) {
	// Limit the number of HTML chars

		$pMax =  strlen($Txt)-1;
		$p=0;
		$n=0;
		$in = false;
		$ok = true;

		while ($ok) {
			if ($in) {
				if ($Txt[$p]===';') {
					$in = false;
					$n++;
				}
			} else {
				if ($Txt[$p]==='&') {
					$in = true;
				} else {
					$n++;
				}
			}
			if (($n>=$Nbr) || ($p>=$pMax)) {
				$ok = false;
			} else {
				$p++;
			}
		}

		if (($n>=$Nbr) && ($p<$pMax)) $Txt = substr($Txt,0,$p).$MaxEnd;

	}




	function f_Xml_GetPart(&$Txt, $TagLst, $AllIfNothing=false) {
	// Returns parts of the XML/HTML content, default is BODY.

		if (($TagLst===true) || ($TagLst==='')) $TagLst = 'body';

		$x = '';
		$nothing = true;
		$TagLst = explode('+',$TagLst);

		// Build a clean list of tags
		foreach ($TagLst as $i=>$t) {
			if ((substr($t,0,1)=='(') && (substr($t,-1,1)==')')) {
				$t = substr($t,1,strlen($t)-2);
				$Keep = true;
			} else {
				$Keep = false;
			}
			$TagLst[$i] = array('t'=>$t, 'k'=>$Keep, 'b'=>-1, 'e'=>-1, 's'=>false);
		}

		$PosOut = strlen($Txt);
		$Pos = 0;

		// Optimized search for all tag types
		do {

			// Search next positions of each tag type
			$TagMin = false;   // idx of the tag at first position
			$PosMin = $PosOut; // pos of the tag at first position
			foreach ($TagLst as $i=>$Tag) {
				if ($Tag['b']<$Pos) {
					$Loc = $this->f_Xml_FindTag($Txt,$Tag['t'],true,$Pos,true,false,false);
					if ($Loc===false) {
						$Tag['b'] = $PosOut; // tag not found, no more search on this tag
					} else {
						$Tag['b'] = $Loc->PosBeg;
						$Tag['e'] = $Loc->PosEnd;
						$Tag['s'] = (substr($Txt,$Loc->PosEnd-1,1)==='/'); // true if it's a single tag
					}
					$TagLst[$i] = $Tag; // update
				}
				if ($Tag['b']<$PosMin) {
					$TagMin = $i;
					$PosMin = $Tag['b'];
				}
			}

			// Add the part of tag types
			if ($TagMin!==false) {
				$Tag = &$TagLst[$TagMin];
				$Pos = $Tag['e']+1;
				if ($Tag['s']) {
					// single tag
					if ($Tag['k']) $x .= substr($Txt,$Tag['b']  ,$Tag['e'] - $Tag['b'] + 1);
				} else {
					// search the closing tag
					$Loc = $this->f_Xml_FindTag($Txt,$Tag['t'],false,$Pos,true,false,false);
					if ($Loc===false) {
						$Tag['b'] = $PosOut; // closing tag not found, no more search on this tag
					} else {
						$nothing = false;
						if ($Tag['k']) {
							$x .= substr($Txt,$Tag['b']  ,$Loc->PosEnd - $Tag['b'] + 1);
						} else {
							$x .= substr($Txt,$Tag['e']+1,$Loc->PosBeg - $Tag['e'] - 1);
						}
						$Pos = $Loc->PosEnd + 1;
					}
				}
			}

		} while ($TagMin!==false);

		if ($AllIfNothing && $nothing) return $Txt;
		return $x;

	}




	/**
	 * Find the start of an XML tag.
	 * $Case=false can be useful for HTML.
	 * $Tag='' should work and found the start of the first tag.
	 * $Tag='/' should work and found the start of the first closing tag.
	 * Encapsulation levels are not featured yet.
	 */
	function f_Xml_FindTagStart(&$Txt, $Tag, $Opening, $PosBeg, $Forward, $Case=true) {
		if ($Txt==='') return false;

		$x = '<'.(($Opening) ? '' : '/').$Tag;
		$xl = strlen($x);

		$p = $PosBeg - (($Forward) ? 1 : -1);

		if ($Case) {
			do {
				if ($Forward) $p = strpos($Txt,$x,$p+1);  else $p = strrpos(substr($Txt,0,$p+1),$x);
				if ($p===false) return false;
				if (substr($Txt,$p,$xl)!==$x) continue; // For PHP 4 only
				$z = substr($Txt,$p+$xl,1);
			} while (!in_array($z, TBX_TAG_NAME_END, true) && !in_array($Tag, ['/',''], true));
		} else {
			do {
				$z = substr($Txt,$p+$xl,1);
			} while (!in_array($z, TBX_TAG_NAME_END, true) && !in_array($Tag, ['/',''], true));
		}

		return $p;

	}




	/**
	 * This function is a smart solution to find an XML tag.
	 * It allows to ignore full opening/closing couple of tags that could be inserted before the searched tag.
	 * It allows also to pass a number of encapsulations.
	 * To ignore encapsulation and opengin/closing just set $LevelStop=false.
	 * $Opening is used only when $LevelStop=false.
	 */
	function f_Xml_FindTag(&$Txt, $Tag, $Opening, $PosBeg, $Forward, $LevelStop, $WithPrm, $WithPos=false) {
		if ($Tag==='_') { // New line
			$p = $this->f_Xml_FindNewLine($Txt,$PosBeg,$Forward,($LevelStop!==0));
			$Loc = new tbxLocator($this);
			$Loc->PosBeg = ($Forward) ? $PosBeg : $p;
			$Loc->PosEnd = ($Forward) ? $p : $PosBeg;
			$Loc->RightLevel = 0;
			return $Loc;
		}

		$Pos = $PosBeg + (($Forward) ? -1 : +1);
		$TagIsOpening = false;
		$TagClosing = '/'.$Tag;
		$LevelNum = 0;
		$TagOk = false;
		$PosEnd = false;
		$TagL = strlen($Tag);
		$TagClosingL = strlen($TagClosing);
		$RightLevel = 0;

		do {

			// Look for the next tag def
			if ($Forward) {
				$Pos = strpos($Txt,'<',$Pos+1);
			} else {
				if ($Pos<=0) {
					$Pos = false;
				} else {
					$Pos = strrpos(substr($Txt,0,$Pos - 1),'<'); // strrpos() syntax compatible with PHP 4
				}
			}

			if ($Pos!==false) {

				// Check the name of the tag
				if (strcasecmp(substr($Txt,$Pos+1,$TagL),$Tag)==0) {
					// It's an opening tag
					$PosX = $Pos + 1 + $TagL; // The next char
					$TagOk = true;
					$TagIsOpening = true;
				} elseif (strcasecmp(substr($Txt,$Pos+1,$TagClosingL),$TagClosing)==0) {
					// It's a closing tag
					$PosX = $Pos + 1 + $TagClosingL; // The next char
					$TagOk = true;
					$TagIsOpening = false;
				}

				if ($TagOk) {
					// Check the next char
					$x = $Txt[$PosX];
					if (in_array($x, TBX_TAG_NAME_END, true) || in_array($Tag, ['/',''], true)) {
						// Check the encapsulation count
						if ($LevelStop===false) { // No encapsulation check
							if ($TagIsOpening!==$Opening) $TagOk = false;
						} else { // Count the number of level
							if ($TagIsOpening) {
								$PosEnd = strpos($Txt,'>',$PosX);
								if ($PosEnd!==false) {
									if ($Txt[$PosEnd-1]==='/') {
										if (($Pos<$PosBeg) && ($PosEnd>$PosBeg)) {$RightLevel=1; $LevelNum++;}
									} else {
										$LevelNum++;
									}
								}
							} else {
								$LevelNum--;
							}
							// Check if it's the expected level
							if ($LevelNum!=$LevelStop) {
								$TagOk = false;
								$PosEnd = false;
							}
						}
					} else {
						$TagOk = false;
					}
				} //--> if ($TagOk)

			}
		} while (($Pos!==false) && ($TagOk===false));

		// Search for the end of the tag
		if ($TagOk) {
			$Loc = new tbxLocator($this);
			if ($WithPrm) {
				$Loc->PrmRead($Txt, $PosX, '\'"', '<', '>', $PosEnd, $WithPos);
			} elseif ($PosEnd === false) {
				$PosEnd = strpos($Txt, '>', $PosX);
				if ($PosEnd === false) {
					$TagOk = false;
				}
			}
		}

		// Result
		if ($TagOk) {
			$Loc->PosBeg = $Pos;
			$Loc->PosEnd = $PosEnd;
			$Loc->RightLevel = $RightLevel;
			return $Loc;
		} else {
			return false;
		}

	}




	function f_Xml_FindNewLine(&$Txt, $PosBeg, $Forward, $IsRef) {

		$p = $PosBeg;
		if ($Forward) {
			$Inc = 1;
			$Inf = &$p;
			$Sup = strlen($Txt)-1;
		} else {
			$Inc = -1;
			$Inf = 0;
			$Sup = &$p;
		}

		do {
			if ($Inf>$Sup) return max($Sup,0);
			$x = $Txt[$p];
			if (($x==="\r") || ($x==="\n")) {
				$x2 = ($x==="\n") ? "\r" : "\n";
				$p0 = $p;
				if (($Inf<$Sup) && ($Txt[$p+$Inc]===$x2)) $p += $Inc; // Newline char can have two chars.
				if ($Forward) return $p; // Forward => return pos including newline char.
				if ($IsRef || ($p0!=$PosBeg)) return $p0+1; // Backwars => return pos without newline char. Ignore newline if it is the very first char of the search.
			}
			$p += $Inc;
		} while (true);

	}

}
