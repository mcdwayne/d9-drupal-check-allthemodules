<?php

/**
 * @file
 * Contains \Drupal\mktcnnt\Controller\MarketoAPIController
 */

namespace Drupal\mktcnnt\Controller;
use Drupal\Core\Controller\ControllerBase;


class MarketoAPIController extends ControllerBase {
	public $marketoSoapEndPoint;
	public $marketoUserId;
	public $marketoSecretKey;
	public $marketoNameSpace;
	public $soapClient;
	public $email;
	
	public function __construct() {
		
	}
	
	public function load() {
		// Create Signature
		$dtzObj = new \DateTimeZone("America/Los_Angeles");
		$dtObj  = new \DateTime('now', $dtzObj);
		$timeStamp = $dtObj->format(DATE_W3C);
		$encryptString = $timeStamp . $this->marketoUserId;
		$signature = hash_hmac('sha1', $encryptString, $this->marketoSecretKey);

		// Create SOAP Header
		$attrs = new \stdClass();
		$attrs->mktowsUserId = $this->marketoUserId;
		$attrs->requestSignature = $signature;
		$attrs->requestTimestamp = $timeStamp;
		$this->authHdr = new \SoapHeader($this->marketoNameSpace, 'AuthenticationHeader', $attrs);
		$this->options = ["connection_timeout" => 20, "location" => $this->marketoSoapEndPoint];
		try{
			$this->soapClient = new \SoapClient($this->marketoSoapEndPoint ."?WSDL", $this->options);
			return true;
		}catch(\Error $e){
			return $e->getMessage();
		}
		
	}
	
	public function testConnection(){
		try{
			$res = self::createLeadinMarketo('abc@xyz.com');
		}catch(\Exception $e){
			return $e->getMessage();
		}
		return $res;
	}
	
	public function createLeadinMarketo($email){
		$leadSyncKey = new \stdClass();
		$leadSyncKey->Email = $email;
		
		$attrArray = [];
		$attrList = new \stdClass();
		$attrList->attribute = $attrArray;
		$leadSyncKey->leadAttributeList = $attrList;

		$leadRecord = new \stdClass();
		$leadRecord->leadRecord = $leadSyncKey;
		$leadRecord->returnLead = false;
		$params = ["paramsSyncLead" => $leadRecord];
		try{	
			$response = $this->soapClient->__soapCall('syncLead', $params, $this->options, $this->authHdr);
		}catch(\SoapFault $e){
			return [false, $e->getMessage()];
		}
		
		return [true, $response];
	}
}
