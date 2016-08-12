<?php



class tbxDatasource {

	public $Type			= false;
	public $SubType			= TBX_DS_SUB0;
	public $SrcId			= false;
	public $RecSet			= false;
	public $RecKey			= '';
	public $RecNum			= 0;
	public $RecNumInit		= 0;
	public $RecSaving		= false;
	public $RecSaved		= false;
	public $RecBuffer		= false;
	public $CurrRec			= false;
	public $TBX				= false;
	public $OnDataOk		= false;
	public $OnDataPrm		= false;
	public $OnDataPrmDone	= [];




	public function DataAlert($Msg) {
		if (tbx_array($this->TBX->_CurrBlock)) {
			return $this->TBX->meth_Misc_Alert(
				'when merging block "'.implode(',',$this->TBX->_CurrBlock).'"', $Msg
			);
		} else {
			return $this->TBX->meth_Misc_Alert(
				'when merging block '.$this->TBX->_ChrOpen.$this->TBX->_CurrBlock.$this->TBX->_ChrClose, $Msg
			);
		}
	}




	public function DataPrepare(&$SrcId, &$TBX) {
		$this->SrcId		= &$SrcId;
		$this->TBX			= &$TBX;
		$FctInfo			= false;
		$FctObj				= false;

		if (tbx_array($SrcId)) {
			$this->Type		= TBX_DS_ARRAY;

		} elseif (is_resource($SrcId)) {
			$FctInfo		= get_resource_type($SrcId);
			$FctCat			= 'r';

		} elseif (is_string($SrcId)) {
			switch (strtolower($SrcId)) {
				case 'array':	$this->Type = TBX_DS_ARRAY;	$this->SubType = TBX_DS_SUB1; break;
				case 'clear':	$this->Type = TBX_DS_ARRAY;	$this->SubType = TBX_DS_SUB3; break;
				case 'text':	$this->Type = TBX_DS_TEXT;		break;
				case 'num':		$this->Type = TBX_DS_NUMBER;	break;
				default:		$FctInfo = $SrcId; $FctCat = 'k';
			}

		} else if (is_a($SrcId, 'pudlResult')) {
			$this->Type		= TBX_DS_PUDL;

		} elseif ($SrcId instanceof Iterator) {
			$this->Type		= TBX_DS_ITERATOR;
			$this->SubType	= TBX_DS_SUB1;

		} elseif ($SrcId instanceof ArrayObject) {
			$this->Type		= TBX_DS_ITERATOR;
			$this->SubType	= TBX_DS_SUB2;

		} elseif ($SrcId instanceof IteratorAggregate) {
			$this->Type		= TBX_DS_ITERATOR;
			$this->SubType	= TBX_DS_SUB3;

		} elseif (is_object($SrcId)) {
			$FctInfo		= get_class($SrcId);
			$FctCat			= 'o';
			$FctObj			= &$SrcId;
			$this->SrcId = &$SrcId;

		} elseif ($SrcId === false) {
			$SrcId			= [];
			$this->Type		= TBX_DS_ARRAY;
			//$this->DataAlert('the specified source is set to FALSE. Maybe your connection has failed.');

		} else {
			$this->DataAlert('unsupported variable type : \''.gettype($SrcId).'\'.');
		}

		if ($FctInfo!==false) {
			$ErrMsg = false;
			$this->Type		= $this->DataAlert($ErrMsg);
		}

		return ($this->Type!==false);
	}




	public function DataOpen(&$Query) {

		// Init values
		unset($this->CurrRec); $this->CurrRec = true;
		if ($this->RecSaved) {
			$this->FirstRec = true;
			unset($this->RecKey); $this->RecKey = '';
			$this->RecNum = $this->RecNumInit;
			if ($this->OnDataOk) $this->OnDataArgs[1] = &$this->CurrRec;
			return true;
		}
		unset($this->RecSet); $this->RecSet = false;
		$this->RecNumInit = 0;
		$this->RecNum = 0;

		if ($this->OnDataOk) {
			$this->OnDataArgs = array();
			$this->OnDataArgs[0] = &$this->TBX->_CurrBlock;
			$this->OnDataArgs[1] = &$this->CurrRec;
			$this->OnDataArgs[2] = &$this->RecNum;
			$this->OnDataArgs[3] = &$this->TBX;
		}

		switch ($this->Type) {
			case TBX_DS_ARRAY:
				if (($this->SubType===TBX_DS_SUB1) && (is_string($Query))) {
					$this->SubType = TBX_DS_SUB2;
				}

				if ($this->SubType===TBX_DS_SUB0) {
					$this->RecSet = &$this->SrcId;
				} elseif ($this->SubType===TBX_DS_SUB1) {
					if (tbx_array($Query)) {
						$this->RecSet = &$Query;
					} else {
						$this->DataAlert('type \''.gettype($Query).'\' not supported for the Query Parameter going with \'array\' Source Type.');
					}
				} elseif ($this->SubType===TBX_DS_SUB2) {
					// TBX query string for array and objects, syntax: "var[item1][item2]->item3[item4]..."
					$x = trim($Query);
					$z = chr(0);
					$x = str_replace(array(']->','][','->','['),$z,$x);
					if (substr($x,strlen($x)-1,1)===']') $x = substr($x,0,strlen($x)-1);
					$ItemLst = explode($z,$x);
					$ItemNbr = count($ItemLst);
					$Item0 = &$ItemLst[0];
					// Check first item
					if ($Item0[0]==='~') {
						$Item0 = substr($Item0,1);
						if ($this->TBX->ObjectRef!==false) {
							$Var = &$this->TBX->ObjectRef;
							$i = 0;
						} else {
							$i = $this->DataAlert('invalid query \''.$Query.'\' because property ObjectRef is not set.');
						}
					} else {
						if (isset($GLOBAL[$Item0])) {
							$Var = &$GLOBAL[$Item0];
							$i = 1;
						} else {
							$i = $this->DataAlert('invalid query \''.$Query.'\' because VarRef item \''.$Item0.'\' is not found.');
						}
					}
					// Check sub-items
					$Empty = false;
					while (($i!==false) && ($i<$ItemNbr) && ($Empty===false)) {
						$x = $ItemLst[$i];
						if (tbx_array($Var)) {
							if (isset($Var[$x])) {
								$Var = &$Var[$x];
							} else {
								$Empty = true;
							}
						} elseif (is_object($Var)) {
							$ArgLst = $this->TBX->f_Misc_CheckArgLst($x);
							if (method_exists($Var,$x)) {
								$f = array(&$Var,$x); unset($Var);
								$Var = call_user_func_array($f,$ArgLst);
							} elseif (property_exists(get_class($Var),$x)) {
								if (isset($Var->$x)) $Var = &$Var->$x;
							} elseif (isset($Var->$x)) {
								$Var = $Var->$x; // useful for overloaded property
							} else {
								$Empty = true;
							}
						} else {
							$i = $this->DataAlert('invalid query \''.$Query.'\' because item \''.$ItemLst[$i].'\' is neither an Array nor an Object. Its type is \''.gettype($Var).'\'.');
						}
						if ($i!==false) $i++;
					}
					// Assign data
					if ($i!==false) {
						if ($Empty) {
							$this->RecSet = array();
						} else {
							$this->RecSet = &$Var;
						}
					}
				} elseif ($this->SubType===TBX_DS_SUB3) { // Clear
					$this->RecSet = array();
				}
				// First record
				if ($this->RecSet!==false) {
					$this->RecNbr = $this->RecNumInit + count($this->RecSet);
					$this->FirstRec = true;
					$this->RecSaved = true;
					$this->RecSaving = false;
				}
			break;


			case TBX_DS_NUMBER:
				$this->RecSet = true;
				$this->NumMin = 1;
				$this->NumMax = 1;
				$this->NumStep = 1;
				if (tbx_array($Query)) {
					if (isset($Query['min'])) $this->NumMin = $Query['min'];
					if (isset($Query['step'])) $this->NumStep = $Query['step'];
					if (isset($Query['max'])) {
						$this->NumMax = $Query['max'];
					} else {
						$this->RecSet = $this->DataAlert('the \'num\' source is an array that has no value for the \'max\' key.');
					}
					if ($this->NumStep==0) $this->RecSet = $this->DataAlert('the \'num\' source is an array that has a step value set to zero.');
				} else {
					$this->NumMax = ceil($Query);
				}
				if ($this->RecSet) {
					if ($this->NumStep>0) {
						$this->NumVal = $this->NumMin;
					} else {
						$this->NumVal = $this->NumMax;
					}
				}
			break;


			case TBX_DS_PUDL:
				case 1: $this->RecSet = &$this->SrcId;
			break;


			case TBX_DS_TEXT:
				if (is_string($Query)) {
					$this->RecSet = &$Query;
				} else {
					$this->RecSet = $this->TBX->meth_Misc_ToStr($Query);
				}
			break;


			case TBX_DS_ITERATOR:
				if ($this->SubType==TBX_DS_SUB1) {
					$this->RecSet = $this->SrcId;
				} else {
					$this->RecSet = $this->SrcId->getIterator();
				}
				$this->RecSet->rewind();
			break;
		}

		if (($this->Type===TBX_DS_ARRAY) || ($this->Type===TBX_DS_ITERATOR)) {
			unset($this->RecKey); $this->RecKey = '';
		} else {
			if ($this->RecSaving) {
				unset($this->RecBuffer); $this->RecBuffer = array();
			}
			$this->RecKey = &$this->RecNum; // Not array: RecKey = RecNum
		}

		return ($this->RecSet!==false);
	}




	public function DataFetch() {

		if ($this->RecSaved) {
			if ($this->RecNum<$this->RecNbr) {
				if ($this->FirstRec) {
					if ($this->SubType===TBX_DS_SUB2) { // From string
						reset($this->RecSet);
						$this->RecKey = key($this->RecSet);
						$this->CurrRec = &$this->RecSet[$this->RecKey];
					} else {
						$this->CurrRec = reset($this->RecSet);
						$this->RecKey = key($this->RecSet);
					}
					$this->FirstRec = false;
				} else {
					if ($this->SubType===TBX_DS_SUB2) { // From string
						next($this->RecSet);
						$this->RecKey = key($this->RecSet);
						$this->CurrRec = &$this->RecSet[$this->RecKey];
					} else {
						$this->CurrRec = next($this->RecSet);
						$this->RecKey = key($this->RecSet);
					}
				}
				if ((!is_array($this->CurrRec)) && (!is_object($this->CurrRec))) $this->CurrRec = array('key'=>$this->RecKey, 'val'=>$this->CurrRec);
				$this->RecNum++;
				if ($this->OnDataOk) {
					$this->OnDataArgs[1] = &$this->CurrRec; // Reference has changed if ($this->SubType===TBX_DS_SUB2)
					if ($this->OnDataPrm) call_user_func_array($this->OnDataPrmRef,$this->OnDataArgs);
					if ($this->SubType!==TBX_DS_SUB2) $this->RecSet[$this->RecKey] = $this->CurrRec; // save modifications because array reading is done without reference :(
				}
			} else {
				unset($this->CurrRec); $this->CurrRec = false;
			}
			return;
		}

		switch ($this->Type) {
			case TBX_DS_PUDL:
				$this->CurrRec = $this->RecSet->row();
			break;

			case TBX_DS_NUMBER:
				if (($this->NumVal>=$this->NumMin) && ($this->NumVal<=$this->NumMax)) {
					$this->CurrRec = array('val'=>$this->NumVal);
					$this->NumVal += $this->NumStep;
				} else {
					$this->CurrRec = false;
				}
			break;

			case TBX_DS_TEXT:
				if ($this->RecNum===0) {
					if ($this->RecSet==='') {
						$this->CurrRec = false;
					} else {
						$this->CurrRec = &$this->RecSet;
					}
				} else {
					$this->CurrRec = false;
				}
			break;

			case TBX_DS_FUNCTION:
				$FctFetch = $this->FctFetch;
				$this->CurrRec = $FctFetch($this->RecSet,$this->RecNum+1);
			break;

			case TBX_DS_OBJECTREF:
				$this->FctPrm[0] = &$this->RecSet; $this->FctPrm[1] = $this->RecNum+1;
				$this->CurrRec = call_user_func_array($this->FctFetch,$this->FctPrm);
			break;

			case TBX_DS_OBJECT:
				$this->CurrRec = $this->SrcId->tbxdb_fetch($this->RecSet,$this->RecNum+1);
			break;

			case TBX_DS_ITERATOR:
				if ($this->RecSet->valid()) {
					$this->CurrRec	= $this->RecSet->current();
					$this->RecKey	= $this->RecSet->key();
					$this->RecSet->next();
				} else {
					$this->CurrRec = false;
				}
			break;
		}

		// Set the row count
		if ($this->CurrRec!==false) {
			$this->RecNum++;
			if ($this->OnDataOk) {
				if ($this->OnDataPrm) call_user_func_array($this->OnDataPrmRef,$this->OnDataArgs);
			}
			if ($this->RecSaving) $this->RecBuffer[$this->RecKey] = $this->CurrRec;
		}
	}




	public function DataClose() {
		$this->OnDataOk		= false;
		$this->OnDataPrm	= false;
		if ($this->RecSaved) return;

		switch ($this->Type) {
			case TBX_DS_PUDL:		$this->RecSet->free(); break;
			case TBX_DS_FUNCTION:	$FctClose=$this->FctClose; $FctClose($this->RecSet); break;
			case TBX_DS_OBJECTREF:	call_user_func_array($this->FctClose, [&$this->RecSet]); break;
			case TBX_DS_OBJECT:		$this->SrcId->tbxdb_close($this->RecSet); break;
		}

		if ($this->RecSaving) {
			$this->RecSet = &$this->RecBuffer;
			$this->RecNbr = $this->RecNumInit + count($this->RecSet);
			$this->RecSaving = false;
			$this->RecSaved = true;
		}
	}

}
