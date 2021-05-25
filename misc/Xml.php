<?php

namespace vihv;

class Xml {

	const WRAP_COUNT = 74;

	public static function makeForest($data) {
		$res = '';
        if($data instanceof Data) {
            foreach($data->getItems() as $key=>$item) {
                //var_dump($key);
                /*if($item instanceof Data) {
                    $res .= self::makeTree($item, $key);
                    continue;
                }*/
                if(is_array($item)) {
                    foreach($item as $itemsItem) {
                        $res .= self::makeTree($itemsItem, $key);
                    }
                    continue;
                }
                if(is_string($item) && !in_array($key,$data->getNoCDATAkeys())){
                    $item = self::cdata($item);
                }
                if(is_int($key)) {
                    $res .= self::makeTree($item, 'item');
                    continue;
                }
                $res .= self::makeTree($item, $key);
            }
            return $res;
        }
		if(is_array($data)) {
			foreach($data as $key=>$value) {
				$temp = $key;
				if(is_int($key)) {
					$temp = "item";
				}
				$res .= self::makeTree($value, $temp);
			}
            return $res;
		} 
        return (string)$data;
	}
    

	public static function makeTree($Data, $Tag, $Attributes = "") {
		$res = self::makeForest($Data);
		return "<".$Tag.$Attributes.">".$res."</".$Tag.">";
		}
        
//    public static function MakeTreeFromData(Data $data, $tag, $attributes = "") {
//        return "<".$tag.$attributes">".self::makeForestFromData($data)."</".$tag.">\n";
//    }

	public static function ToArray($Xml,$keyItem = "item") {
		return self::FromSimpleXml(new \SimpleXMLElement($Xml),$keyItem);
		}
	/**
	 * @return string surrounded with CDATA
	 */
	public static function cdata($string) {
		if(!is_string($string)) {
//			var_dump($string);
		}
		return "<![CDATA[".str_replace("<![CDATA[","",str_replace("]]>","",$string))."]]>"; 
	}

	public static function FromSimpleXml($XmlObject, $keyItem = "item") {
		
		if(count($XmlObject) == 0) {
			return (string)$XmlObject;
			}
		$res = array();
		foreach($XmlObject->children() as $child) {
			
			if($child->getName() == $keyItem) {
				$res[] = self::FromSimpleXml($child);
				} else {
				if(!isset($res[$child->getName()])) {
					$res[$child->getName()] = self::FromSimpleXml($child,$keyItem);
					} else {
					if(is_string($res[$child->getName()])) {
						$temp = $res[$child->getName()];
						$res[$child->getName()] = array();
						$res[$child->getName()][] = $temp;
						}
					$res[$child->getName()][] = self::FromSimpleXml($child,$keyItem);
					}
				}
			};
		return $res;
		}
		
	public static function FormatSimpleXml($xml) {
		$lt = "<span class='highline'>&#60;";
		$gt = "&#62;</span>";
		$children = $xml->children();
		$text = $xml->__toString();
		$attrs = $xml->attributes();
		$attrStr = '';
		foreach($attrs as $key=>$value) {
			$attrStr .= " ".$key."='".$value."'";
		}
		$html = '';
		if(count($children) > 0 ) {
			$html .= $lt.$xml->getName().$attrStr.$gt;
			$html .= "<dir>";
			foreach($children as $child) {
				$html .= self::FormatSimpleXml($child);
			}
			$html .= "</dir>";
			$html .= $lt."/".$xml->getName().$gt."<br/>";
		} elseif($text == '') {
			$html .= $lt.$xml->getName().$attrStr."/".$gt."<br/>";
		} else {
			$html .= $lt.$xml->getName()."".$attrStr.$gt;
			$html .= htmlspecialchars($text);
			$html .= $lt."/".$xml->getName().$gt."<br/>";
		}
		return $html;
	}
	/**
	 * Format and Highline Xml 
	 * @param $String XML as string
	 * @return HTML representing formatted XML
	 */	
	public static function FormatXml($String) {
		$xml = new \SimpleXMLElement($String);
		return self::FormatSimpleXml($xml);
		}
	}