<?php

require(dirname(__FILE__) . "/Snoopy.class.php");

/**
 * Class Thebing_Snippet
 * @author Thebing Services GmbH
 */
class Thebing_Snippet {

	/**
	 * @var string
	 */
	protected $_sUrl;

	/**
	 * @var string
	 */
	protected $_sCode;

	/**
	 * @var string
	 */
	protected $_sTemplate;

	/**
	 * @var int
	 */
	protected $_iTimeout = 10;

	/**
	 * @var
	 */
	protected $_sContent;

	/**
	 * @var string
	 */
	protected $_sCharset = 'utf-8';

	/**
	 * @var array
	 */
	protected $_aUserParams = array();

	/**
	 * @param string $sUrl
	 * @param string $sCode
	 * @param string $sTemplate
	 * @param string $sCharset
	 */
	public function __construct($sUrl, $sCode, $sTemplate = '', $sCharset='utf-8') {

		$this->_sUrl = $sUrl;
		$this->_sCode = $sCode;
		$this->_sTemplate = $sTemplate;
		$this->_sCharset = strtolower($sCharset);

	}

	/**
	 * @return string
	 */
	public function getContent() {

		$sContent = $this->_sContent;

		if($this->_sCharset != 'utf-8') {
			$sContent = iconv('utf-8', $this->_sCharset.'//TRANSLIT', $sContent);
		}

		return $sContent;
	}

	/**
	 * @param int $iTimeout
	 */
	public function setTimeout($iTimeout) {
		$this->_iTimeout = $iTimeout;
	}

	/**
	 * @param string $sInput
	 */
	public function convertInput(&$sInput) {

		if(is_scalar($sInput)) {
			$sInput = iconv($this->_sCharset, 'utf-8//TRANSLIT', $sInput);
		}

	}

	/**
	 * @return array
	 */
	protected function _getRequest() {

		$_REQUEST['REQUEST_URI'] = $_SERVER['REQUEST_URI'];
		$aRequest = $_REQUEST;

		if($this->_sCharset != 'utf-8') {
			array_walk_recursive($aRequest, array($this, 'convertInput'));
		}

		return $aRequest;
	}

	/**
	 * The main method of the PlugIn
	 *
	 * @return string The content that is displayed on the website
	 */
	function execute() {

		$sUrl = $this->_sUrl;
		$sCode = $this->_sCode;
		$sTemplate = $this->_sTemplate;

		if(
			empty($sUrl) ||
			empty($sCode)
		) {
			$sContent = 'Error: Configuration data is missing!';

			$sContent .= ' (URL: '.$sUrl.', Code: '.$sCode.', Template: '.$sTemplate.')';

		} else {

			$oSnoopy = new Snoopy;
			$oSnoopy->read_timeout = $this->_iTimeout;

			if(strpos($sUrl, '/wdmvc/') === false) {
				$sHostName = $this->getHostUrl($sUrl);
				$sUrl = $sHostName . '/system/extensions/tc_api.php';
			}

			$aVariables = array();

			$aRequest = $this->_getRequest();

			// Add all request parameters to snoopy request
			if(!empty($aRequest)) {
				foreach((array)$aRequest as $mKey=>$mValue) {
					$aVariables[$mKey] = $mValue;
				}
			}

			$aVariables['code'] = $sCode;
			
			if(!empty($sTemplate)) {
				$aVariables['template'] = $sTemplate;
			}

			if(!empty($this->_aUserParams)) {
				$aVariables['frontend_combination_params'] = $this->_aUserParams;
			}

			$aFiles = array();

			if(!empty($_FILES)) {

				$sTempDir = sys_get_temp_dir();
				if(!is_writeable($sTempDir)) {
					die('Fatal error while uploading file');
				}

				// Save all files to temporary directory
				foreach((array)$_FILES as $sKey => $mItems) {
					if(!is_array($mItems['name'])) {
						$sTarget = $sTempDir . '/' . $mItems['name'];
						$bSuccess = move_uploaded_file($mItems['tmp_name'], $sTarget);
						if($bSuccess) {
							$aFiles[$sKey] = $sTarget;
						}
					} else {
						$this->_prepareFiles($mItems['name'], $mItems['tmp_name'], $aFiles[$sKey], $sTempDir);
					}
				}

			}

			unset($_COOKIE['PHPSESSID']);
			unset($aVariables['PHPSESSID']);

			if(
				isset($_COOKIE['thebing_snippet_session_id']) &&
				isset($_COOKIE['thebing_snippet_session_name'])
			) {
				$_COOKIE[$_COOKIE['thebing_snippet_session_name']] = $_COOKIE['thebing_snippet_session_id'];
			}

			$aVariables['X-Originating-IP'] = $_SERVER['REMOTE_ADDR'];
			$aVariables['X-Originating-Agent'] = $_SERVER['HTTP_USER_AGENT'];
			$aVariables['X-Originating-Host'] = $_SERVER['HTTP_HOST'];
			$aVariables['X-Originating-URI'] = $_SERVER['REQUEST_URI'];
			$aVariables['X-Originating-HTTPS'] = $_SERVER['HTTPS'];
			
			// Add cookie data to snoopy request
			$oSnoopy->cookies = $_COOKIE;
			$oSnoopy->set_submit_multipart();
			$oSnoopy->submit($sUrl, $aVariables, $aFiles);

			if(
				(
					isset($_REQUEST['task']) &&
					(
						$_REQUEST['task'] == 'get_js' ||
						$_REQUEST['task'] == 'get_image' ||
						$_REQUEST['task'] == 'get_file' ||
						$_REQUEST['task'] == 'get_ajax'
					)
				) ||
				isset($_REQUEST['get_request']) ||
				isset($_REQUEST['get_file'])

			) {

				$this->passHeaders($oSnoopy);

				while(ob_get_level() > 0) {
					ob_end_clean();
				}

				$sContent = $oSnoopy->results;
				echo $sContent;
				die();

			} else {

				$sContent = $oSnoopy->results;

				if(empty($sContent)) {
					if($oSnoopy->timed_out == 1) {
						$sContent = 'Connection timeout exceeded! Please reload this page.';
					} else {
						$sContent = $oSnoopy->error;
					}
				}

			}

			$this->passCookies($oSnoopy);

			$this->passHeaders($oSnoopy);
			
			if(!empty($aFiles)) {
				$this->_unlinkFiles($aFiles);
			}

		}

		$this->_sContent = $sContent;

	}

	/**
	 * @param mixed|array $mItems
	 * @param mixed|array $mTmpItems
	 * @param array $aFiles
	 * @param string $sTempDir
	 */
	protected function _prepareFiles($mItems, $mTmpItems, &$aFiles, $sTempDir) {

		foreach((array)$mItems as $sKey => $aItems) {
			if(!is_array($aItems)) {
				$sTarget = $sTempDir . '/' . $mItems[$sKey];
				$bSuccess = move_uploaded_file($mTmpItems[$sKey], $sTarget);
				if($bSuccess) {
					$aFiles[$sKey] = $sTarget;
				}
			} else {
				$this->_prepareFiles($mItems[$sKey], $mTmpItems[$sKey], $aFiles[$sKey], $sTempDir);
			}
		}

	}

	/**
	 * @param array $aFiles
	 */
	protected function _unlinkFiles(&$aFiles) {

		foreach((array)$aFiles as $mKey => $mFile) {
			if(is_array($mFile)) {
				$this->_unlinkFiles($aFiles[$mKey]);
			} else if(is_file($mFile)) {
				unlink($mFile);
			}
		}

	}

	/**
	 * @param string $sString
	 * @param bool|string $sKey
	 */
	public function encodeString(&$sString, $sKey = false) {
		global $aConfig;

		if(
			is_string($sString) &&
			$aConfig['charset'] != 'utf-8'
		) {
			$sString = iconv($aConfig['charset'], 'utf-8', $sString);
		}

	}

	/**
	 * @param string $sString
	 * @param bool|string $sKey
	 */
	public function decodeString(&$sString, $sKey = false) {
		global $aConfig;

		if(
			is_string($sString) &&
			$aConfig['charset'] != 'utf-8'
		) {
			$sString = iconv('utf-8', $aConfig['charset'], $sString);
		}

	}

	/**
	 * @param bool $sHost
	 * @return bool|string
	 */
	public function getHostUrl($sHost=false) {

		if(!empty($sHost) && strpos($sHost, 'http') === false) {
			$sHost = 'http://'.$sHost;
		}

		return $sHost;
	}

	/**
	 * @param string $sKey
	 * @param mixed $mValue
	 * @return Thebing_Snippet
	 */
	public function setCombinationParameter($sKey, $mValue) {
		$this->_aUserParams[$sKey] = $mValue;
		return $this;
	}

	/**
	 * @param Snoopy $oSnoopy
	 */
	private function passCookies($oSnoopy) {
		foreach((array)$oSnoopy->cookies as $sKey=>$mValue) {

			if(get_magic_quotes_gpc()) {
				$mValue = stripslashes($mValue);
			}

			if(is_scalar($mValue)) {
				if(
					!isset($_COOKIE[$sKey]) &&
					$sKey != 'PHPSESSID' &&
					$sKey != session_name()
				) {
					setcookie($sKey, $mValue, 0, '/');					
					$_COOKIE[$sKey] = $mValue;
				}
			}

		}
	}

	/**
	 * @param Snoopy $oSnoopy
	 */
	private function passHeaders($oSnoopy) {
		foreach((array)$oSnoopy->headers as $sHeader) {

			if(get_magic_quotes_gpc()) {
				$sHeader = stripslashes($sHeader);
			}

			if(
				strpos($sHeader, 'Content-Type') !== false ||
				strpos($sHeader, 'Content-Disposition') !== false ||
				strpos($sHeader, 'Cache-Control') !== false
			) {
				if(headers_sent()) {
					die('Headers has already been send! '.__CLASS__.' can\'t continue!');
				}

				header($sHeader, true);
			}

		}
	}

}
