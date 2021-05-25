<?php 

namespace vihv;

require_once "vihv/interface/IControl.php";
require_once "vihv/interface/IControlConfig.php";
require_once 'vihv/interface/IHaveTemplate.php';
require_once "vihv/misc/Xml.php";
require_once "vihv/event/EventManager.php";
require_once "vihv/config/ConfigManager.php";
require_once 'vihv/misc/File.php';


/**
base class for all controls
control abstracts functional part of aplication
*/
class Control implements IControl, IHaveTemplate {

	/**
	 * @var Data
	this variable store associative array that will be used in GetXml and GetHtml
	you should shange this variable in event handlers (by modifying $Sender->Data) if you want control to display result of your calculations, or result of sql request or whatever
	*/
	var $data;

	/**
	 * @var \vihv\Container parent control
	 */
	private $Container;
//	var $Config;

	/**
	 * @var boolean
	 */
	private $enabled;
	
	/**
	 * @var \vihv\ITheme
	 */
	private $theme;
	
	/**
	 * @var \vihv\EventManager
	 */
	private $eventManager;

	/**
	 * create object and declare basic events, then runs onCreate event
	 */
	public function __construct() {
		$this->enabled = false;
		$this->data = new Data();
		$this->setEvent('onGet', array($this,'onGetEvent'));
		$this->setEvent('onPost', array($this,'onPostEvent'));
		$this->setEvent('onDisplay', array($this,'onDisplayEvent'));
		$this->setEvent('onBeforeDisplay', array($this,'onBeforeDisplayEvent'));
		$this->setEvent('onEnable', array($this,'onEnableEvent'));
		$this->setEvent('onDisable', array($this,'onDisableEvent'));
//		$this->setEvent('onCreate', array($this,'onCreateEvent'));
		$this->setEvent('onParked', array($this,'onParkedEvent'));
		$this->setEvent('onRootTemplateLoad', array($this,'onRootTemplateLoadEvent'));
		$this->setEvent('onDefaultPage', array($this,'onDefaultPageEvent'));
//		$this->setEvent('onParented', array($this,'onParentedEvent'));
		$this->onCreateEvent($this);
		}
		
	/**
	 * $eventManager EventManaget
	 */
	public function setEventManager(EventManager $eventManager) {
		$this->eventManager = $eventManager;
	}
	
	public function dropEventManager() {
		$this->eventManager = null;
	}
		
	/**
	 * @return EventManager
	 * @throws EControlEventManagerNotSet
	 */
	public function getEventManager() {
		$parent = $this->getParent();
		if(!empty($parent)) {
			return $parent->getEventManager();
		}
		if(empty($this->eventManager)) {
			throw new EControlEventManagerNotSet(get_class($this));
		}
		return $this->eventManager;
	}	
	
	/**
	 * enable this control, then run onEnable event
	 */
	public function enable() {
		if(!$this->allowed('onEnable')) {
			return;
		}
		$this->enabled = true;
		$this->onEnable();
	}

	/**
	 * disable this control, then run onDisable event
	 */
	public function disable() {
		if(!$this->allowed('onDisable')) {
			return;
		}
		$this->enabled = false;
		$this->onDisable();
	}

	/**
	 * 
	 * @return bool true if enabled
	 */
	public function isEnabled() {
		return $this->enabled;
		}

	/**
	 * @return string root tag for GetXml
	 */
	public function getRootTag() {
		return str_replace("\\","-",get_class($this));
	}
	
	/**
	 * 
	 * @return string resourceId used by ACL system, you can allow/deny/drop usage of the resource
	 */
	public function getResourceId() {
		return $this->getRootTag();
		//return get_class($this);
		}
	/**
	 * override this to add attributes to root xml tag
	 * @return array attributes for root xml tag (used by getXml), key=>value
	 */
	public function getAttributes() {
		return array();
	}
	/**
	 * @return string xml attributes as string
	 */
	public function getXmlAttributes() {
		$res = "";
		foreach($this->GetAttributes() as $key=>$value) {
			$res .= " ".$key."=\"".$value."\"";
		}
		return $res;
	}

	/**
	 * call theme to get xslt template filename, you can override it for each control
	 * if tou do not want to use themes
	 * @return filename (with path) to XSLT template needed for this control
	 */
	public function getTemplate() {
		//return ConfigManager::getTemplate($this->getRootTag());
		$theme = $this->getTheme();
		if(!($theme instanceof ITheme)) {
			throw new EControThemeIsNotInstanceofITheme(get_class($this)." theme: ".get_class($theme));
		}
		return $theme->getTemplate($this->getRootTag());
	}

	/**
	 * @param number $code
	 * @param string $message
	 * @throws EXslError
	 */
	public function warningHandler($code, $message) {
		throw new EXslError("Code: ".$code." ".$message, $code);
	}
	
	/**
	 * set theme for current control, in most cases should be used only for root control
	 * theme needed to handle relation between control and xmlt template (view level)
	 * you can avoid using themes by overriding getTemplate method for each control
	 * @param \vihv\ITheme $theme
	 */
	public function setTheme(ITheme $theme) {
		$this->theme = $theme;
	}
	
	/**
	 * @return ITheme theme to be used by this control, if no theme set - it will return parent control theme
	 */
	public function getTheme() {
		$parent = $this->getParent();
		if(!empty($parent)) {
			return $parent->getTheme();
		}
		if(empty($this->theme)) {
			throw new EThemeNotSet("theme for control ".  get_class($this)."not found, use setTheme()");
		}
		return $this->theme;
	}
	
	
//	function GetEntities() {
//		return '<!ENTITY nbsp "&#160;">';
//	}
	
	/**
	 * 
	 * @return \DOMDocument XSLT template for this control
	 * @throws ETemplateParseError
	 */
	public function getXSLT() {
		$dom = new \DOMDocument('1.0','UTF-8');
//		$xslt = /*$this->GetEntities()."\n".*/file_get_contents(TFile::SearchIncludePath($this->GetTemplate()));
//		var_dump($xslt);
		$filename = File::SearchIncludePath($this->getTemplate());
        //var_dump($filename);
        //$content = file_get_contents($filename);
        //var_dump($content);
        //if(strpos($content, '<xsl:stylesheet') === false) {
         //   $content = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="xml" encoding="utf-8" indent="no"/><xsl:template match="Site">'.$content.'</xsl:stylesheet>';
        //}
        //var_dump($content);
		if(@$dom->load($filename)===false){
			throw new ETemplateParseError($filename);
		}
		$this->onRootTemplateLoad($dom);
		return $dom;
	}
	
	/**
	 * @param $format can be 'html' or 'xml', dafault if 'html'
	 * @return xslt output
	 */
	public function getHtml($format = 'html') {

		$oldval = ini_get('track_errors');
		ini_set('track_errors', true);
		global $php_errormsg;
		
		
		$XslProcessor = new \XSLTProcessor();
//		$this->DOM = new DOMDocument('1.0','UTF-8');
//		echo 'in control get html';
//		var_dump($this->GetTemplate());
//		if(@$this->DOM->load(TFile::SearchIncludePath($this->GetTemplate()))===false){
//			throw new ETemplateNotFound();
//		}
		set_error_handler(array($this, 'warningHandler'), E_WARNING);
		$xslt = $this->getXSLT();
//		$this->OnRootTemplateLoad($xslt);
        //var_dump($xslt);
		if($XslProcessor->importStylesheet($xslt) === false) {
            //var_dump('bb'); exit;
            //$xslt = '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="xml" encoding="utf-8" indent="no"/><xsl:template match="Site">'.$xslt.'</xsl:stylesheet>';
            //if($XslProcessor->importStylesheet($xslt) === false) {
            // /   var_dump($xslt);
                throw new EXslError($php_errormsg);
            //}
		}
   
		restore_error_handler();
		$this->DOM = new \DOMDocument('1.1','UTF-8');
		$this->DOM->loadXML('<?xml version="1.0" encoding="UTF-8"?>'.$this->GetXml(),LIBXML_COMPACT | LIBXML_PARSEHUGE);
//		var_dump($this->GetXml());
		@$Doc = $XslProcessor->transformToDoc($this->DOM);
		if($Doc === false) {
			throw new EXslError($php_errormsg);
		}
		ini_set('track_errors', $oldval);
		
		if($format == 'html') {
			return str_replace("<br></br>","<br/>",$Doc->saveHTML());
		}
		return $Doc->saveXML();
	}

	/**
	 * serialize control data as xml
	 * @return string
	 */
	public function getXml() {
			try {
				$XmlData = Xml::makeTree($this->getDataObject(), $this->getRootTag(),$this->getXmlAttributes());
				//var_dump($XmlData);
				$Xml = simplexml_load_string($XmlData, 'SimpleXMLElement', LIBXML_COMPACT | LIBXML_PARSEHUGE);
//				$Xml = new \SimpleXmlElement($XmlData);
                //$XmlData = $Xml->asXML();
				return $XmlData;
			} catch(Exception $e) {
			}
		return "<".$this->GetRootTag().$this->GetXmlAttributes()."/>";
		}

	/**
	 * set output data
	 * @param Array or Data $data associative array with basic types only, this data will be serialized as xml and sent to view level or Data object
	 */
	public function setData($data) {
		if($data instanceof Data) {
			$this->data = $data;
			return;
		}
		if(is_array($data)) {
			$this->data->importArray($data);
			return;
		}
		throw EWrongDataType();
	}
	/**
	 * add element to control's data, data will be serialized as xml and sent to view level
	 * @param string $key
	 * @param any $value
	 */
	public function pushData($key, $value, $noCDATA = false) {
		$this->data->push($key, $value, $noCDATA);
	}
	
	/**
	 * add bool element to control's data, data will be serialized as xml and sent to view level,
	 * if value is false - nothing will be added
	 * @param string $key
	 * @param bool $value
	 * @throws EControlPushBool
	 */
	public function pushBoolData($key, $value) {
		if(!is_bool($value)) {
			throw new EControlPushBool('value is not bool, but '.get_type($value));
		}
		if($value) {
			$this->pushData($key, $value);
		}
	}
	/**
	 * get output data as array
	 * @return Array associative array of data to be displayed
	 */
	public function getData($nocdata = false) {
		return $this->data->asArray($nocdata);
	}
	
	public function getDataObject() {
		return $this->data;
	}

	/**
	 * set another control as parent control for this one, then call onParented event
	 * @param \vihv\Container $Container
	 */
	public function setParent(Container $Container) {
		$this->Container = $Container;
		$this->onParented();
		}

	/**
	 * 
	 * @return \vihv\Container parent control
	 */
	public function getParent() {
		return $this->Container;
		}

	public function allowed($eventName) {
		return $this->getEventManager()->CheckPermission($this->GetResourceId(),$eventName);
	}
	/**
	 * call event handler
	 *  - checks if user is allowed to perform the action via ACL
	 *  - checks if control provide event hander for the action
	 *  
	 * @param string $EventName
	 * @param any $Args
	 * @return any what event handler returns
	 * @throws EControlEventNotFound
	 */
	public function __call($EventName, $Args) {
//		var_dump($EventName);
//		$Allowed = $this->getEventManager()->CheckPermission($this->GetResourceId(),$EventName);
		if( !$this->provide($EventName) ) {
			throw new EControlEventNotFound($EventName);
			}
		if($this->allowed($EventName) && $this->provide($EventName)) {
			return call_user_func($this->Event[$EventName], $this, reset($Args)); // only first element we need
			}
		}

	/**
	Example $Sender->SetEvent('OnEnable', array($this, 'OnEnableEvent'));
	@param $Name  name of event, event will be called like $Sender->OnEnable()
	@param $Handler array(object what contains method, method name) where "method" is a fuction what does all real work
	*/
	public function setEvent($Name, $Handler) {
		$this->Event[$Name] = $Handler;
		}

	/**
	 * 
	 * @param string $EventName
	 * @return boolean true if this control provide event handler for the event with specified name
	 */
	public function provide($EventName) {
		if(!empty($this->Event[$EventName])) {
			return true;
			}
		return false;
		}

	/**
	redirect to THE SAME page but only with GET method, this almost be the page with form you submit, should be used in OnPostEvent or Events raised by OnPostEvent
	*/
	public function goBack() {
		Header("Location: ".$_SERVER['REQUEST_URI']);
		}

	/**
	 * to be overriden by child class
	this is default (does nothing) event handler, you can set it to be any function, even from another class 
	@param $Input  - same as global $_GET, but after passing validation, should be safe for interaction with database 
	@param $Sender - as for $this wont work if handler will be defined outside control, use $Sender instead. $Sender points to the control.
	Common usage - modify $Sender->Data
	*/
	public function onGetEvent($Sender,$Input) {}
	
	/**
	 * to be overriden by child class
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onPostEvent($Sender,$Input) {}
	
	/**
	 * to be overriden by clind class
	 * this event raised right after constructor
	 * @param \vihv\Control $sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onCreateEvent($sender) {}
	
	/**
	 * to be overriden by child class
	 * default event handler for onParked,
	 * onParked is rising when eventManager park this control (set it as listener, see EventManager::park, EventManager::parkMany)
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onParkedEvent($sender) {}
	/**
	 * to be overriden by child class
	 * default handler for onRootTemplateLoad event
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 * @param \DomDocument $DOM
	 */
	public function onRootTemplateLoadEvent($Sender, $DOM) {}
	/**
	 * to be overriden by child class
	 * default handler for onDisplay event
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onDisplayEvent($Sender) {}
	/**
	 * to be overriden by child class
	 * default handler for onEnable event
	 * good place to collect data from models and push it with Control::pushData to view lavel
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onEnableEvent($sender) {}
	/**
	 * to be overriden by child class
	 * default handler for onDisable event
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onDisableEvent($Sender) {}
	/**
	 * @deprecated since version 2.0.0
	 * to be overriden by child class
	 * default handler for onDefaultPageEvent
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onDefaultPageEvent($Sender) {}
	
	/**
	 * to be overriden by child class
	 * default handler for onBeforeDisplay
	 * the last event before page rendering
	 * @param \vihv\Control $Sender object which provide this event handler, in most cases $sender is equal to $this, unless several different controls use the same event handler
	 */
	public function onBeforeDisplayEvent($Sender) {}
	
	/**
	 * call onParented event without using ACL (because ALC is unavailable at that moment),
	 * but same coding style, do mot call this manually
	 */
	public function onParented() {
		$this->onParentedEvent();
	}
	/**
	 * default handler for onParented event
	 */
	public function onParentedEvent() {}
	}
	
class ETemplateNotFound extends Exception {}
class ETemplateParseError extends Exception {}
class EXslError extends Exception {}
class EControlPushBool extends Exception {}
class EControlEventManagerNotSet extends Exception {}
class EControThemeIsNotInstanceofITheme extends Exception {}
