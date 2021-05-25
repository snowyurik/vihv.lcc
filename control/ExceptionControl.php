<?php
namespace vihv;

class ExceptionControl extends Control {
	
	const DEFAULT_TEMPLATE = "vihv/design/Misc/Exception.xsl";
	
	public function SetException($e)  {
		$this->setData([
			'class' => get_class($e),
			'message' => $e->getMessage(),
			'full' => (string)$e,
			'siteurl' => Url::getSiteUrl(),
		]);
		}
                
        function SetTemplate($path) {
            $this->template = $path;
        }
		
	function GetTemplate() {
		try {
			return parent::GetTemplate();
		} catch(Exception $e) {
                    if(!empty($this->template)) {
			return $this->template;
                    }
                    return self::DEFAULT_TEMPLATE;
		}
		}
	}