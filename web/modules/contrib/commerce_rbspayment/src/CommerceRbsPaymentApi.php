<?php

namespace Drupal\commerce_rbspayment;

use Drupal\Core\Url;
use Exception;


/**
 * Sends requests to the server encoding request parameters and decoding
 * results with given encoder and decoder. returns error if a timeout occurs
 */
class CommerceRbsPaymentApi {

  private $rbsApiTestUrl = '';
  private $rbsApiProdUrl = '';

  const maxFileReadingStringLength = 4096;
  const internalErrorCode = 10000;

  const orderStatusPending = 0;
  const orderStatusPreHold = 1;
  const orderStatusAuthorized = 2;
  const orderStatusReversed = 3;
  const orderStatusPartlyRefunded = 4;
  const orderStatusAuthACS = 5;
  const orderStatusDeclined = 6;

  public static $statusDescriptions = array(
    0 => 'Зарегистрирован, не оплачен',
    1 => 'Сумма захолдирована',
    2 => 'Полная авторизация суммы',
    3 => 'Авторизация отменена',
    4 => 'Проведён возврат',
    5 => 'Авторизация ACS',
    6 => 'Авторизация отклонена',
  );

  /**
   * @var string
   *  Well-formed server url to connect with
   */
  private $serverUrl;

  /**
   * @var int
   *  Timeout for server communications in seconds.
   */
  private $timeout;

  /**
   * @var string
   */
  private $userName;

  /**
   * @var string
   */
  private $password;

  /**
   * @var boolean
   */
  private $isDoubleStaged;

  /**
   * @var boolean
   */
  private $isLogging;

  /**
   * CommerceRbsPaymentApi constructor.
   *
   * @param string $userName
   * @param string $password
   * @param int $timeout
   * @param boolean $is_double_staged
   * @param boolean $is_test_mode
   * @param boolean $is_logging
   */
  public function __construct($url, $test_url, $userName, $password, $timeout, $is_double_staged, $is_test_mode = FALSE, $is_logging = FALSE) {
    $this->rbsApiTestUrl = $test_url;
    $this->rbsApiProdUrl = $url;
    $this->userName = $userName;
    $this->password = $password;
    $this->isDoubleStaged = $is_double_staged;
    $this->serverUrl = $is_test_mode ? $this->rbsApiTestUrl : $this->rbsApiProdUrl;
    $this->timeout = $timeout;
    $this->isLogging = $is_logging;
  }

  /**
   * @param int $communication_start_time
   *
   * @return bool
   */
  private function isTimedOut($communication_start_time) {
    return (time() - $communication_start_time) >= $this->timeout;
  }

  /**
   * Sends data compressed by deflate algorithm to the server.
   * 
   * @param string $method_url_suffix
   *  Url suffix.
   * @param array $data
   *  Data to be send to server.
   *
   * @return array
   *  response.
   */
  protected function callMethod($method_url_suffix, array $data = array()) {
    try {
      $data['userName'] = $this->userName;
      $data['password'] = $this->password;
      
      $stream_context = $this->createStreamContext($data);

      $communication_start_time = time();

      $fp = fopen("{$this->serverUrl}{$method_url_suffix}", 'r', FALSE, $stream_context);

      if (!$fp) {
        $this->throwConnectionException($communication_start_time, t("Problem occurred while connecting to bank's server."));
      }
      stream_set_blocking($fp, FALSE);

      $response = $this->readResponse($fp);
      fclose($fp);

      if (empty($response)) {
        $this->throwConnectionException($communication_start_time, t("Problem occurred while reading data from bank's server."));
      }

      $result = json_decode($response, TRUE);

      if ($this->isLogging) {
        if (isset($result['errorCode']) && $result['errorCode']) {
          \Drupal::logger('commerce_rbspayment')->warning('API "%payment_method" call, ErrorCode = "%error_code", message = "%message", <br/> post: %post<br/> response: %response', array(
            '%payment_method' => $method_url_suffix,
            '%error_code' => isset($result['errorCode']) ? $result['errorCode'] : '',
            '%message' => isset($result['errorMessage']) ? $result['errorMessage'] : '',
            '%post' => htmlentities(print_r($data, TRUE)),
            '%response' => htmlentities($response),
          ));
        }
        else {
          \Drupal::logger('commerce_rbspayment')->debug('API "%payment_method" call, ErrorCode = "%error_code", message = "%message", <br/> post: %post<br/> response: %response', array(
            '%payment_method' => $method_url_suffix,
            '%error_code' => isset($result['errorCode']) ? $result['errorCode'] : '',
            '%message' => isset($result['errorMessage']) ? $result['errorMessage'] : '',
            '%post' => htmlentities(print_r($data, TRUE)),
            '%response' => htmlentities($response),
          ));
        }
      }

      return $result;
    }
    catch (Exception $e) {
      $errorCode = isset($result['errorCode']) ? $result['errorCode'] : '';
      if ($errorCode) {
        \Drupal::logger('commerce_rbspayment')->warning('API "%payment_method" call, ErrorCode = "%error_code", message = "%message", <br/> post: %post', array(
          '%payment_method' => $method_url_suffix,
          '%error_code' => $errorCode,
          '%message' => $e->getMessage(),
          '%post' => htmlentities(print_r($data, TRUE)),
        ));
      }
      else {
        \Drupal::logger('commerce_rbspayment')->debug('API "%payment_method" call, ErrorCode = "%error_code", message = "%message", <br/> post: %post', array(
          '%payment_method' => $method_url_suffix,
          '%error_code' => $errorCode,
          '%message' => $e->getMessage(),
          '%post' => htmlentities(print_r($data, TRUE)),
        ));
      }

      return array(
        'errorCode' => self::internalErrorCode,
        'errorMessage' => $e->getMessage(),
      );
    }
  }

  /**
   * @param array $data
   *
   * @return mixed resource
   */
  private function createStreamContext(array $data) {
    $context_params = array(
      'http' => array(
        'method' => 'POST',
        'content' => http_build_query($data),
        'timeout' => $this->timeout,
        'header' => 'Content-type: application/x-www-form-urlencoded;charset="utf-8"',
      ),
      'ssl' => array(
        'verify_peer' => FALSE,
        'verify_host' => 1,
      ),
    );
    return stream_context_create($context_params);
  }

  /**
   * @param int $communication_start_time
   * @param string $message
   * 
   * @throws Exception
   */
  private function throwConnectionException($communication_start_time, $message) {
    $timeout_msg = $this->isTimedOut($communication_start_time) ? t('Bank server response time exceeded (@timeout)', array('@timeout' => $this->timeout)) : '';
    throw new Exception($message . ' ' . $timeout_msg);
  }

  /**
   * @param mixed $fp
   *  File pointer.
   *
   * @return string
   *  Read string.
   */
  private function readResponse($fp) {
    $response = '';

    while (!feof($fp)) {
      $response .= fgets($fp, self::maxFileReadingStringLength);
    }

    return $response;
  }

  /**
   * @param int $order_id
   * @param int $amount
   * @param string $currency_iso_code
   * @param string $return_url
   * @param string $fail_url
   * @param string $description
   * @param string $language
   * @param array $json_params
   * @param string $page_view
   *
   * @return array
   */
  function registerOrder($order_id, $amount, $currency_iso_code, $return_url, $fail_url, $description, $language,
                         array $json_params = array(), $page_view = 'DESKTOP') {
    $data = array(
      'orderNumber' => $order_id,
      'amount' => $amount,
      'currency' => $currency_iso_code,
      'returnUrl' => $return_url,
      'description' => $description,
      'language' => $language,
      'pageView' => $page_view,
    );
    if ($fail_url != $return_url) {
      $data['failUrl'] = $fail_url;
    }
    if ($json_params) {
      $data['jsonParams'] = json_encode($json_params);
    }
    $method = $this->isDoubleStaged ? 'registerPreAuth.do' : 'register.do';

    return $this->callMethod($method, $data);
  }

  /**
   * @param string $rbs_order_id
   * @param int $amount
   *
   * @return mixed
   */
  public function confirmOrderPayment($rbs_order_id, $amount = 0) {
    return $this->callMethod('deposit.do', array(
      'orderId' => $rbs_order_id,
      'amount' => $amount,
    ));
  }

  /**
   * @param int $order_id
   *
   * @return array
   */
  public function getOrderStatusByOrderId($order_id) {
    return $this->callMethod('getOrderStatusExtended.do', array('orderNumber' => $order_id));
  }

  /**
   * @param string $rbs_order_id
   *
   * @return mixed
   */
  public function getOrderStatusByRBSOrderId($rbs_order_id) {
    return $this->callMethod('getOrderStatusExtended.do', array('orderId' => $rbs_order_id));
  }

  /**
   * @param string $rbs_order_id
   *
   * @return mixed
   */
  public function reverseOrderPayment($rbs_order_id) {
    return $this->callMethod('reverse.do', array('orderId' => $rbs_order_id));
  }

  /**
   * @param string $rbs_order_id
   * @param int $amount
   *
   * @return mixed
   */
  public function refundOrderPayment($rbs_order_id, $amount) {
    return $this->callMethod('refund.do', array(
      'orderId' => $rbs_order_id,
      'amount' => $amount
    ));
  }

  /**
   * @param string $date
   * @param array $transaction_states
   * @param bool $search_by_created_date
   * @param int $num_entries
   *
   * @return array
   */
  public function getOperationsList($date, $transaction_states = array(), $search_by_created_date = TRUE, $num_entries = 100) {
    if (!$transaction_states) {
      $transaction_states = array('CREATED', 'APPROVED', 'DEPOSITED', 'DECLINED', 'REVERSED', 'REFUNDED');
    }

    $params = array(
      'from' => "{$date}000000",
      'to' => "{$date}235959",
      'size' => $num_entries,
      'transactionStates' => implode(', ', $transaction_states),
      'searchByCreatedDate' => $search_by_created_date,
      'merchants' => '',
    );
    return $this->callMethod('getLastOrdersForMerchants.do', $params);
  }

}
