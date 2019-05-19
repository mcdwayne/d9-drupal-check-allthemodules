<?php
namespace Drupal\swish_payment;

use Drupal\Core\Url;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Handler\CurlFactory;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\ResponseInterface;
use Drupal\swish_payment\Entity\SwishTransaction;

/**
 * Class that handles calling Swish API.
 * http://www.payexpim.com/category/pxorder/
 *
 * @param boolean $testMode Decides if running testmode or not.
 * @param boolean $md5 set to true for MD5 hash algorithm, false for SHA1.
 */
class SwishClient {
  const SWISH_PRODUCTION_URL = 'https://swicpc.bankgirot.se/swish-cpcapi/api/v1';
  const SWISH_TEST_URL = 'https://mss.swicpc.bankgirot.se/swish-cpcapi/api/v1';

  /**
   * @var string
   */
  private $baseURL;

  /**
   * @var \GuzzleHttp\ClientInterface
   */
  private $client;

  public function __construct($client, $liveMode) {
    $this->client = $client;
    $this->baseURL = ($liveMode ? self::SWISH_PRODUCTION_URL : self::SWISH_TEST_URL);
  }

  /**
   * @param string $payerAlias The mobile number of the payment device
   * @param string $amount The amount in SEK
   * @param string $payeePaymentReference Optional order ID or similar
   * @param string $message Optional message to be shown in the app.
   * @return SwishTransaction
   */
  public function createPaymentRequest($payerAlias, $amount, $payeePaymentReference = null, $message = null) {
    try {
      $callbackUrl = Url::fromRoute("swish_payment.callback_update", [], ['https'=>TRUE, 'absolute'=>TRUE])->toString();
      $config = \Drupal::config('swish_payment.settings');
      $payerAlias = self::normalizePhoneNo($payerAlias);
      $data = [
        "payeePaymentReference" => $payeePaymentReference,
        "callbackUrl" => $callbackUrl,
        "payerAlias" => $payerAlias,
        "payeeAlias" => $config->get('payee_alias'),
        "amount" => $amount,
        "currency" => 'SEK', //SEK only, sorry guys.
        "message" => $message,
      ];

      $response = $this->sendRequest('POST', '/paymentrequests', $data);
      $statusCode = $response->getStatusCode();
      $trans = false;
      if($statusCode == 201) {
        $locationParts =  explode("/", reset($response->getHeader('Location')));
        $transactionId = array_pop($locationParts);
        $trans = SwishTransaction::create();
        $trans->setTransactionId($transactionId);
        $trans->setPayeePaymentReference($payeePaymentReference);
        $trans->setPayerAlias($payerAlias);
        if($message)
          $trans->setMessage($message);
        $trans->setAmount($amount);
        $trans->setStatus('CREATED');
        $trans->save();
      }
      else {
        drupal_set_message(t('Error !code', ['!code' => $statusCode]), 'error');
        /*TODO: more error logging here*/
      }
      return $trans;
    }
    catch(Exception $e) {
      drupal_set_message(t('An error occured !e', ['!e' => $e->getMessage()]), 'error');
      /*TODO: more error logging here*/
    }
    return false;
  }

  /**
   * @param string $transactionId The transaction ID
   * @return SwishTransaction
   */
  public function retrievePaymentRequest($transactionId) {
    try {
      $response = $this->sendRequest('GET', '/paymentrequests/'.$transactionId);
      $statusCode = $response->getStatusCode();
      $trans = false;
      if($statusCode == 200) {
        if($trans = SwishTransaction::Load($transactionId)) {
          $data = json_decode($response->getBody());
          if($data['paymentReference'])
            $trans->setPaymentReference($data['paymentReference']);
          if($data['status'])
            $trans->setStatus($data['status']);
          if($data['datePaid'])
            $trans->setPaidTime(strtotime($data['datePaid']));
          if($data['errorCode'])
            $trans->setErrorCode($data['errorCode']);
          if($data['errorMessage'])
            $trans->setErrorMessage($data['errorMessage']);
          if($data['additionalInformation'])
            $trans->setAdditionalInformation($data['additionalInformation']);
          $trans->save();
        }
        return $trans;
      }
      else {
        drupal_set_message(t('Error !code', ['!code' => $statusCode]), 'error');
      }
    }
    catch(Exception $e) {
      drupal_set_message(t('Some other error !e', ['!e' => $e->getMessage()]), 'error');
    }
    return false;
  }

  /**
  * @return SwishClient
  */
  public static function create()
  {
    $config = \Drupal::config('swish_payment.settings');

    $key = $config->get('private_key');
    $key_pw = $config->get('private_key_pw');
    if($key_pw)
      $key = [$key, $key_pw];

    $cert = $config->get('client_cert');
    $cert_pw = $config->get('client_cert_pw');
    if($cert_pw)
      $cert = [$cert, $cert_pw];

    $liveMode = $config->get('live_mode');

    $ca_verification = !($config->get('disable_ca_verification'));
    $root_ca = $config->get('ca_cert');

    $cconfig = [
        'http_errors' => false,
        'verify' => ($root_ca ? $root_ca : $ca_verification),
        'cert' => $cert,
        'ssl_key' => $key,
        'handler' => HandlerStack::create(new CurlHandler()),
    ];
    $guzzle = new \GuzzleHttp\Client($cconfig);
    return new SwishClient($guzzle, $liveMode);
  }

  /**
   * @param string $method HTTP-method
   * @param string $endpoint Service endpoint to be called
   * @param mixed $data any json_encode accepted data
   * @return Guzzle\Http\Message\Response
   */
  protected function sendRequest($method, $endpoint, $data = null)
  {
    $return = false;
    if($data)
      $return = $this->client->request($method, $this->baseURL . $endpoint, ['json' => $data]);
    else
      $return = $this->client->request($method, $this->baseURL . $endpoint);
    return $return;
  }

  /**
   * @param string $phoneNo
   * @return string
   */
  protected static function normalizePhoneNo($phoneNo)
  {
    $phoneNo = preg_replace("/[^0-9]*/", "", $phoneNo);
    if(strpos($phoneNo, "0") === 0)
      $phoneNo = substr($phoneNo, 1);
    if(strpos($phoneNo, "46") === FALSE)
      $phoneNo = "46".$phoneNo;
    return $phoneNo;
  }

  /**
   * @param string $phoneNo
   * @return string
   */
  public static function validatePhoneNo($phoneNo)
  {
    $phoneNo = self::normalizePhoneNo($phoneNo);
    return strlen($phoneNo)>=8 && strlen($phoneNo)<=15;
  }

}
