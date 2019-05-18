<?php

//
//echo 'NTLMSoapClient.php is a 3rd party workaround for NTLM authentication. ';
//one must use this or similar techiques in order to make PHP Soap Extention
//work against .NET Web Services.
//

//
//XiPaySoapClient - call XiPayWS from this class
//


namespace Drupal\commerce_paymetric\lib;
use Exception;
use \SimpleXMLElement;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

// All available StreamWrapper classes and interfaces
//---------------------------------------------------
use Drupal\Core\StreamWrapper\LocalReadOnlyStream;
use Drupal\Core\StreamWrapper\LocalStream;
use Drupal\Core\StreamWrapper\PhpStreamWrapperInterface;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\ReadOnlyStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StreamWrapper\TemporaryStream;


use Drupal\commerce_paymetric\lib\NTLMSoapClient;
use Drupal\commerce_paymetric\lib\XiPaySoapOpTemplate;
use Drupal\commerce_paymetric\lib\TransactionResponse;



class XiPaySoapClient extends NTLMSoapClient implements ContainerAwareInterface, StreamWrapperManagerInterface {

    use ContainerAwareTrait;

  /**
   * Contains stream wrapper info.
   *
   * An associative array where keys are scheme names and values are themselves
   * associative arrays with the keys class, type and (optionally) service_id,
   * and string values.
   *
   * @var array
   */
  protected $info = [];

  /**
   * Contains collected stream wrappers.
   *
   * Keyed by filter, each value is itself an associative array keyed by scheme.
   * Each of those values is an array representing a stream wrapper, with the
   * following keys and values:
   *   - class: stream wrapper class name
   *   - type: a bitmask corresponding to the type constants in
   *     StreamWrapperInterface
   *   - service_id: name of service
   *
   * The array on key StreamWrapperInterface::ALL contains representations of
   * all schemes and corresponding wrappers.
   *
   * @var array
   */
  protected $wrappers = [];

  /**
   * {@inheritdoc}
   */
  public function getWrappers($filter = StreamWrapperInterface::ALL) {
    if (isset($this->wrappers[$filter])) {
      return $this->wrappers[$filter];
    }
    elseif (isset($this->wrappers[StreamWrapperInterface::ALL])) {
      $this->wrappers[$filter] = [];
      foreach ($this->wrappers[StreamWrapperInterface::ALL] as $scheme => $info) {

        // Bit-wise filter.
        if (($info['type'] & $filter) == $filter) {
          $this->wrappers[$filter][$scheme] = $info;
        }
      }
      return $this->wrappers[$filter];
    }
    else {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getNames($filter = StreamWrapperInterface::ALL) {
    $names = [];
    foreach (array_keys($this
      ->getWrappers($filter)) as $scheme) {
      $names[$scheme] = $this
        ->getViaScheme($scheme)
        ->getName();
    }
    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescriptions($filter = StreamWrapperInterface::ALL) {
    $descriptions = [];
    foreach (array_keys($this
      ->getWrappers($filter)) as $scheme) {
      $descriptions[$scheme] = $this
        ->getViaScheme($scheme)
        ->getDescription();
    }
    return $descriptions;
  }

  /**
   * {@inheritdoc}
   */
  public function getViaScheme($scheme) {
    return $this
      ->getWrapper($scheme, $scheme . '://');
  }

  /**
   * {@inheritdoc}
   */
  public function getViaUri($uri) {
    $scheme = file_uri_scheme($uri);
    return $this
      ->getWrapper($scheme, $uri);
  }

  /**
   * {@inheritdoc}
   */
  public function getClass($scheme) {
    if (isset($this->info[$scheme])) {
      return $this->info[$scheme]['class'];
    }
    return FALSE;
  }

  /**
   * Returns a stream wrapper instance.
   *
   * @param string $scheme
   *   The scheme of the desired stream wrapper.
   * @param string $uri
   *   The URI of the stream.
   *
   * @return \Drupal\Core\StreamWrapper\StreamWrapperInterface|bool
   *   A stream wrapper object, or false if the scheme is not available.
   */
  protected function getWrapper($scheme, $uri) {
    if (isset($this->info[$scheme]['service_id'])) {
      $instance = $this->container
        ->get($this->info[$scheme]['service_id']);
      $instance
        ->setUri($uri);
      return $instance;
    }
    return FALSE;
  }

  /**
   * Adds a stream wrapper.
   *
   * Internal use only.
   *
   * @param string $service_id
   *   The service id.
   * @param string $class
   *   The stream wrapper class.
   * @param string $scheme
   *   The scheme for which the wrapper should be registered.
   */
  public function addStreamWrapper($service_id, $class, $scheme) {
    $this->info[$scheme] = [
      'class' => $class,
      'type' => $class::getType(),
      'service_id' => $service_id,
    ];
  }

  /**
   * Registers the tagged stream wrappers.
   *
   * Internal use only.
   */
  public function register() {
    foreach ($this->info as $scheme => $info) {
      $this
        ->registerWrapper($scheme, $info['class'], $info['type']);
    }
  }

  /**
   * Unregisters the tagged stream wrappers.
   *
   * Internal use only.
   */
  public function unregister() {

    // Normally, there are definitely wrappers set for the ALL filter. However,
    // in some cases involving many container rebuilds (e.g. WebTestBase),
    // $this->wrappers may be empty although wrappers are still registered
    // globally. Thus an isset() check is needed before iterating.
    if (isset($this->wrappers[StreamWrapperInterface::ALL])) {
      foreach (array_keys($this->wrappers[StreamWrapperInterface::ALL]) as $scheme) {
        stream_wrapper_unregister($scheme);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function registerWrapper($scheme, $class, $type) {

    if (in_array($scheme, stream_get_wrappers(), TRUE)) {
      stream_wrapper_unregister($scheme);
    }
    if (($type & StreamWrapperInterface::LOCAL) == StreamWrapperInterface::LOCAL) {
      stream_wrapper_register($scheme, $class);
    }
    else {
      stream_wrapper_register($scheme, $class, STREAM_IS_URL);
    }

    // Pre-populate the static cache with the filters most typically used.
    $info = [
      'type' => $type,
      'class' => $class,
    ];
    $this->wrappers[StreamWrapperInterface::ALL][$scheme] = $info;
      
    if (($type & StreamWrapperInterface::WRITE_VISIBLE) == StreamWrapperInterface::WRITE_VISIBLE) {
      $this->wrappers[StreamWrapperInterface::WRITE_VISIBLE][$scheme] = $info;
    }
  }

    protected $user;
    protected $password;
    protected $serviceURL; 
    
    public $Trace = false;
    public $TraceFile;

    //
    //Constructor for XIPaySoapClient
    //Sets up the stream protocol handler
    //    
    function __construct($XiPayServiceURL, $XiPayUser, $XiPayUserPassword){
    
        $this->serviceURL = $XiPayServiceURL;
        $this->user = "paymetric\\".$XiPayUser; 
        $this->password = $XiPayUserPassword;

        
        $this->unregister();

        $this->registerWrapper('http', 'Drupal\commerce_paymetric\lib\XiPaySoapClient', STREAM_IS_URL);

        //SoapClient without WSDL
        parent::__construct(NULL, array("location" => $this->serviceURL, "uri" => $this->serviceURL));
    }

    //
    //Destructor 
    //    
    function __destruct(){
        //restore the original http protocol
        stream_wrapper_restore('http');
    }
   
    //
    //Authorize a transaction 
    //    
    public function Authorize($transaction){
        $transaction->PacketOperation = 1;//authorization 
        return $this->SoapOp($transaction);
    }

    private function SoapOp($transaction)
    {
        try{
        	
            $request = XiPaySoapOpTemplate::PrepareTransactionXML($transaction);
            $transactionResponse = $this->GetTransactionResponse($this->DoRequest($request));
        }catch(Exception $e){
        	
            $transactionResponse = new TransactionResponse();
            $transactionResponse->Status = STATUS_CLIENT_ERROR;
            $transactionResponse->Message = $e->getMessage();
        }
        
        return $transactionResponse;
    }
    //
    //Get transaction response from SOAP XML response 
    //
    private function GetTransactionResponse($xmlResponse)
    {
        $transactionResponse = new TransactionResponse();
        
        if (is_string($xmlResponse)){
            $transactionResponse->Status = STATUS_SERVER_ERROR;
            $transactionResponse->Message = $xmlResponse;
        }else{
            $transactionResponse->Status = STATUS_OK;
            $transaction = new PaymetricTransaction();
            $th = $xmlResponse->packets->ITransactionHeader;
            foreach(get_object_vars($th) as $key => $value)
            {
                $touchedValue = $value;
                if (is_object($touchedValue)) $touchedValue = "";
                
                if ($key == "InfoItems") $touchedValue = $this->GetInfoItems($value, $transaction);
                else if ($key == "LineItems") $this->GetLineItems($value, $transaction);
                else if ($key == "CheckImages") $this->GetCheckImages($value, $transaction);
                else if ($key == "BlobItems") $touchedValue = array();
                else $transaction->$key = $touchedValue;
            }

            //promote the message to response object             
            $transactionResponse->Message = $transaction->Message;
            $transactionResponse->Transaction = $transaction;
        }
        //var_dump($transactionResponse);
        return $transactionResponse;
    }

    //
    //Add all info items to transaction object 
    //
    private function GetInfoItems($itemItemsObject, &$transaction)
    {
        foreach($itemItemsObject->InfoItem as $infoItem)
        {
            //print_r($infoItem);
            foreach($infoItem->children() as $child)
            {
                if ($child->getName() == "Key") $key = $child;
                else if ($child->getName() == "Value") $value = $child;
            }
            
            $transaction->AddInfoItem((string)$key, (string)$value);
        }
        
        //var_dump($transaction);
    }
    
    //
    //Add all line items to transaction object
    //
    private function GetLineItems($lineItemsObject, &$transaction)
    {
        foreach($lineItemsObject->LineItem as $lineItem)
        {
            $myLineItem = new LineItem();
            foreach($lineItem->children() as $child)
            {
                $name = $child->getName();
                if ($name == "InfoItems") {
                    $this->GetInfoItems($child, $myLineItem);
                }else{
                    $myLineItem->$name = (string) $child;
                }
            }
            
            //var_dump($myLineItem);
            $transaction->AddLineItem($myLineItem);
        }
    }

    //
    //Fish up all check images (though check image never come back from authorization)
    //
    private function GetCheckImages($checkImagesObject, &$transaction)
    {
        foreach($checkImagesObject->CheckImage as $checkImage)
        {
            $myCheckImage = new CheckImage();
            foreach($checkImage->children() as $child)
            {
                $name = $child->getName();
                if ($name == "InfoItems") {
                    $myCheckImage->$name = (string) $child;
                }
            }
            
            var_dump($myCheckImage);
            $transaction->AddCheckImage($myCheckImage);
        }
    }
    
    //
    //Manual Authorize 
    //
    public function ManualAuthorize($transaction){
        $transaction->PacketOperation = 12;
        return $this->SoapOp($transaction);
    }

    //
    //Capture a transaction 
    //    
    public function Capture($transaction){
    
        try{
            $transaction->PacketOperation = 17;//capture
            $request = XiPaySoapOpTemplate::PrepareTransactionXML($transaction);
            $transactionResponse = $this->GetTransactionResponse($this->DoRequest($request));
        }catch(Exception $e){
            $transactionResponse = new TransactionResponse();
            $transactionResponse->Status = STATUS_CLIENT_ERROR;
            $transactionResponse->Message = $e->getMessage();
        }
        
        return $transactionResponse;
    }

    //
    //Void a transaction 
    //    
    public function Void($transactionID)
    {
        $transaction = new PaymetricTransaction();
        $transaction->TransactionID = $transactionID;
       
        //Set these two properties to get around validation 
        //though, they are not required for VOID operation 
        
        $transaction->BatchID = "FAKE-BATCH-ID"; 
        $transaction->SettlementAmount = 0;
        
        $transaction->PacketOperation = 10;
        return $this->SoapOp($transaction);
    }

    //
    //Schedule a batch 
    //    
    public function ScheduleBatch($XIID, $batchID){
        $transaction = new PaymetricTransaction();
        $transaction->XIID = $XIID;
        $transaction->BatchID = $batchID;

        //To pass the transaction validation         
        $transaction->TransactionID = 0;
        $transaction->SettlementAmount = 0;
        
        $transaction->PacketOperation = 18;  
        $transactionResponse = $this->SoapOp($transaction);
        $batchResponse = new BatchResponse();
        $batchResponse->XIID = $transactionResponse->Transaction->XIID;
        $batchResponse->BatchID = $transactionResponse->Transaction->BatchID;
        $batchResponse->Status =  $transactionResponse->Status;
        $batchResponse->Message =  $transactionResponse->Message;
        return $batchResponse;
    }      
    
    //
    //Private methods to manipulate transaction data 
    //
	private function XMLEscape(&$transactionData){ //passed by reference 

		//taking care of XML special chars 
		if (is_array($transactionData)){
			for($i = 0; $i< count($transactionData); $i++){
				if (is_string($transactionData[$i])){
					$transactionData[$i] = htmlentities($transactionData[$i]);
				}else if (is_array($transactionData[$i])){
					$this->XMLEscape($transactionData[$i]);
				}
			}
		}
	}

    //
    //Build info items XML from an array of arrays 
    //
    private function GetLineItemsXML ($lineItemsData){
        $lineItemsXML = "";
        if (is_array($lineItemsData)){
            foreach ($lineItemsData as $lineItemData){
                if (is_array($lineItemData)){
                    $infoItemsData =  $lineItemData[count($lineItemData)-1];
                    if (is_array($infoItemsData)){
                        //replace last entry with XML; 
                        $lineItemData[count($lineItemData)-1] = $this->GetInfoItemsXML($infoItemsData);
                    }
                    
                    $lineItemsXML .= MakeLineItem($lineItemData);
                }
            }
        }
        return $lineItemsXML;
    }
    
    //
    //Build info items XML from an array of arrays 
    //
    private function GetInfoItemsXML($infoItemsData){
        $infoItemsXML = "";
        if (is_array($infoItemsData)){
            foreach($infoItemsData as $infoItem){
                if (is_array($infoItem)){
                    $infoItemsXML .= MakeInfoItem($infoItem);
                }
            }
        }
        
        return $infoItemsXML;
    }
    
    //
    //Build SoapOp payload from transaction data (array of stuff)
    //
    private function GetTransactionXML($transactionData, $replaces, $template){
        if (is_array($transactionData)){
        
			//This will recursively take care of all elements 
			$this->XMLEscape($transactionData);

            //move to last entry 
            $index = count($transactionData)-1;
            
            if ($index >= 0){ //still valid index 
                $lineItems = $transactionData[$index];
                
                if (is_array($lineItems)){
                    //replace array with XML string data 
                    $transactionData[$index] = $this->GetLineItemsXML($lineItems);
                }
            }
            
            //move to the info items 
            $index--;
            
            if ($index >= 0){
                $infoItems = $transactionData[$index];
                if (is_array($infoItems)){
                    $transactionData[$index] = $this->GetInfoItemsXML($infoItems);
                }
            }
            
            return str_replace($replaces, $transactionData, $template);
        }
        
        return $transactionXML;
    }
    
    //
    //Call __doRequest and return either error string or ITransactionHeader in SimpleXML format
    //
    private function DoRequest($request){
        $this->trace("__doRequest Request", $request);
        $response = $this->__doRequest($request, $this->serviceURL, "SoapOp", SOAP_1_2);
        $this->trace("__doRequest Response", $response);

        //
        //PRE-PROCESSING RESPONSE RECEIVED FROM SERVER FOR SIMPLE XML PARSER
        //
        $noNameSpace = str_replace(array("soap:Body", "soap:Envelope", "soap:Fault"), 
                                   array("Body", "Envelope", "Fault"), 
                                   $response);
        //print ($noNameSpace);                    

        $xml = new SimpleXMLElement($noNameSpace);
        //var_dump($xml);

        //
        //RETRIEVE TRANSACTION FIELDS FROM RESPONSE 
        //
        if (!isset($xml->Body->Fault)) {
            return $xml->Body->SoapOpResponse->SoapOpResult;
        }else{
          //soap error 
          return $xml->Body->Fault->faultstring;
        }
    }
    
    //
    //Trace function 
    //
    private function trace($message, $traceData){
      if ($this->Trace == false) return;
      date_default_timezone_set('UTC');
      $handle = 0;
      $timestamp = date('Y-m-d-H:i:s');
      if (isset($this->TraceFile)) {
        $handle = fopen($this->TraceFile, "a");
        if ($handle != 0){
          fwrite($handle, $timestamp. " " .$message. "\r\n");
          fwrite ($handle, $traceData . "\r\n\r\n");
          fclose($handle);
        }
      }
      
      //write to console 
      if ($handle == 0){
      }
    }
};

?>