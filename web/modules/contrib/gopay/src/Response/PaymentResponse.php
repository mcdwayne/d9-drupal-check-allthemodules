<?php

namespace Drupal\gopay\Response;

use GoPay\Http\Response;
use GoPay\Definition\Response\PaymentStatus;

/**
 * Class PaymentResponse.
 *
 * @package Drupal\gopay\Response
 */
class PaymentResponse implements PaymentResponseInterface {

  /**
   * GoPay Response object.
   *
   * @var \GoPay\Http\Response
   */
  protected $response;

  /**
   * GoPayFactory constructor.
   *
   * @param \GoPay\Http\Response $response
   *   GoPay response object.
   */
  public function __construct(Response $response) {
    $this->response = $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->response->json['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderNumber() {
    return $this->response->json['order_number'];
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return $this->response->json['state'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSubState() {
    return isset($this->response->json['sub_state']) ? $this->response->json['sub_state'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getAmount() {
    return $this->response->json['amount'];
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrency() {
    return $this->response->json['currency'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentInstrument() {
    return $this->response->json['payment_instrument'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPayer() {
    return $this->response->json['payer'];
  }

  /**
   * {@inheritdoc}
   */
  public function getTarget() {
    return $this->response->json['target'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalParams() {
    return $this->response->json['additional_params'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalParam($name) {
    $params = $this->getAdditionalParams();

    if (count($params) > 0) {
      foreach ($params as $param) {
        if ($param['name'] == $name) {
          return $param['value'];
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getLang() {
    return $this->response->json['lang'];
  }

  /**
   * {@inheritdoc}
   */
  public function getGwUrl() {
    return $this->response->json['gw_url'];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseJson() {
    return $this->response->json;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSucceed() {
    return $this->response->hasSucceed();
  }

  /**
   * {@inheritdoc}
   */
  public function isPaid() {
    if ($this->hasSucceed() && $this->getState() == PaymentStatus::PAID) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isCancelled() {
    if ($this->hasSucceed() && $this->getState() == PaymentStatus::CANCELED) {
      return TRUE;
    }

    return FALSE;
  }

}
