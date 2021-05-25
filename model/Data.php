<?php

namespace vihv;

/**
 * used by Control
 */
class Data {
	
	private $items;
	private $noCDATAkeys = [];
	
	public function __construct() {
		$this->items = [];
	}
	
    public function getItems() {
        return $this->items;
    }
    
    public function getNoCDATAkeys() {
        return $this->noCDATAkeys;
    }
    
	public function importArray(Array $array) {
		foreach($array as $key=>$value) {
			$this->push($key, $value);
		}
//		$this->items = $array;
	}
	
	public function push($key, $value, $noCDATA = false) {
		if(is_array($value)) {
			$this->pushArray($key, $value, $noCDATA);
			return;
		}
		if($value instanceof Data) {
			$this->pushDataObject($key, $value, $noCDATA);
            return;
		}
		if($noCDATA) {
			$this->noCDATAkeys[] = $key;
		}
         //var_dump('data_push_'.$key);
		$this->items[$key] = $value;
	}
	
	public function pushArray($key, $value, $noCDATA = false) {
		$subdata = new Data();
		foreach($value as $k=>$val) {
			$subdata->push($k, $val, $noCDATA);
		}
		$this->pushDataObject($key, $subdata, $noCDATA);
	}
	
	public function pushDataObject($key, $subdata, $noCDATA = false) {
		if($noCDATA) {
			$this->noCDATAkeys[] = $key;
		}
        // var_dump('data_'.$key);
        if(empty($this->items[$key])) {
            //var_dump('first key');
            $this->items[$key] = $subdata;
            return;
        }
        if(is_array($this->items[$key])) {
            //var_dump("3rd key, already array");
            $this->items[$key][] = $subdata;
            return;
        }
        if(!is_array($this->items[$key])) {
            //var_dump('second key, not array (expected true)', $this->items[$key] instanceof Data);
            $temp = clone $this->items[$key];
            unset($this->items[$key]);
            $this->items[$key] = Array();
            $this->items[$key][] = $temp;
            //var_dump('second key, should be array now (expected false)', $this->items[$key] instanceof Data);
//            if(!is_array($this->items[$key])) {
//                throw new \Exception('wtf not array');
//            }
        }
        $this->items[$key][] = $subdata;
//        var_dump($this->items[$key]);
	}
	
	public function asArray($nocdata = false) {
		$re = [];
		foreach($this->items as $key=>$value) {
			if($value instanceof Data) {
				$re[$key] = $value->asArray($nocdata);
				continue;
			}
			if(is_bool($value)) {
				$re[$key] = $value;
				continue;
			}
			if(in_array($key, $this->noCDATAkeys)
				|| $nocdata
				) {
//				var_dump($key,$value);
				$re[$key] = $value;
				continue;
			}
            if(is_array($value)) {
                $re[$key] = [];
                foreach($value as $val) {
                    if($val instanceof Data) {
                        $re[$key][] = $val->asArray();
                        continue;
                    }
                    throw new \Exception('val is not instance of data, should not ne like that');
                }
                continue;
            }
			$re[$key] = \vihv\Xml::cdata($value);
		}
//		var_dump($re);
		return $re;
	}
	
}
