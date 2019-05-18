<?php

namespace Drupal\webpay;

use Drupal\Core\Url;
use Freshwork\Transbank\CertificationBag;
use Freshwork\Transbank\TransbankServiceFactory;
use Drupal\webpay\Entity\WebpayConfigInterface;
use Drupal\webpay\Entity\WebpayTransactionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * The services Webpay Normal.
 */
class WebpayNormalService {

  /**
   * Records errors messages.
   */
  protected $errors = [];

  /**
   * The webpay config of a commerce code.
   */
  protected $webpay_config;

  /**
   * The Transbank service.
   */
  protected $service;

  /**
   * The commerce system id.
   */
  protected $commerceSystemPluginId;

  /**
   * The commerce system plugin instance.
   */
  protected $commerceSystemPlugin;


  /**
   *
   * @param WebpayConfigInterface $webpay_config
   * @param string $commerceSystemPluginId
   */
  public function __construct(WebpayConfigInterface $webpay_config, $commerceSystemPluginId) {
    $this->webpay_config = $webpay_config;

    $this->commerceSystemPluginId = $commerceSystemPluginId;
    $this->commerceSystemPlugin = \Drupal::service('plugin.manager.webpay_commerce_system')
      ->createInstance($commerceSystemPluginId);

    if ($webpay_config->get('log')) {
      $webpay_config->activeLog();
    }

    $bag = new CertificationBag(
      $webpay_config->get('private_key'),
      $webpay_config->get('client_certificate'),
      $webpay_config->get('server_certificate'),
      $webpay_config->get('environment')
    );

    $this->service = TransbankServiceFactory::normal($bag);
  }


  /**
   * Execute the initTransaction Services.
   *
   * @param int $buyOrder
   * @param float $amount
   * @param Url $finalUrl
   * @param mixed $session_id
   * @return mixed
   */
  public function initTransaction($buyOrder, $amount, Url $finalUrl, $session_id = NULL) {
    $service = $this->service;

    $service->addTransactionDetail($amount, $buyOrder);

    $returnUrl = new Url('webpay.webpay_controller_return', [
      'commerce_system_id' => $this->commerceSystemPluginId,
      'webpay_config' => $this->webpay_config->id(),
    ], ['absolute' => TRUE]);

    return $service->initTransaction($returnUrl->toString(), $finalUrl->toString(), $session_id);
  }


  /**
   *
   * @param string $token
   *
   * @return mixed
   */
  public function getTransactionResult($token) {
    try {
      $response = $this->service->getTransactionResult($token);
      if ($response) {
        $isValid = $this->service->acknowledgeTransaction($token);
        if ($isValid) {
          return $response;
        }
        else {
          $this->addError('acknowledgeTransaction', t('The transaction is not valid. Check the keys.'));
        }
      }
    }
    catch (\Exception $e) {
      $this->addError('getTransactionResult', t('The transaction is not valid. Check the keys.'));
    }

    return FALSE;
  }


  /**
   * Invoke the transaction accepted.
   */
  public function invokeTransactionAccepted(WebpayTransactionInterface $transaction) {
    $this->commerceSystemPlugin->transactionAccepted($this->webpay_config, $transaction);
  }


  /**
   * Invoke the transaction rejected.
   */
  public function invokeTransactionRejected(WebpayTransactionInterface $transaction) {
    return $this->commerceSystemPlugin->transactionRejected($this->webpay_config, $transaction);
  }


  /**
   * Get errors.
   */
  public function getErrors($type = NULL) {
    if ($type && isset($this->errors[$type])) {
      $errors = $this->errors[$type];
    }
    else {
      $errors = $this->errors;
    }
    $this->clearErrors($type);

    return $errors;
  }


  /**
   * Add an error message from any type.
   *
   * @param string $type
   *  A type of error.
   * @param string $message
   *  A message of error.
   */
  protected function addError($type, $message) {
    $this->errors[$type][] = $message;
  }


  /**
   * A helper function.
   *
   * Cleans the errors array of some type or all types.
   *
   * @param mixed $type
   *  A type of error.
   *
   * @return WebpayNormalService
   */
  protected function clearErrors($type = NULL) {
    if ($type && isset($this->errors[$type])) {
      unset($this->errors[$type]);
    }
    else {
      $this->errors = [];
    }

    return $this;
  }
}
