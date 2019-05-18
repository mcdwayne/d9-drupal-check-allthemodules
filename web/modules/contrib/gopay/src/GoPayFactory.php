<?php

namespace Drupal\gopay;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\gopay\Contact\Contact;
use Drupal\gopay\Item\Item;
use Drupal\gopay\Payment\StandardPayment;
use Drupal\gopay\Response\PaymentResponse;

/**
 * Class GoPayFactory.
 *
 * @package Drupal\gopay
 */
class GoPayFactory implements GoPayFactoryInterface {

  /**
   * ConfigFactory Service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * GoPayApi Service.
   *
   * @var \Drupal\gopay\GoPayApiInterface
   */
  protected $goPayApi;

  /**
   * GoPayFactory constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory service.
   * @param \Drupal\gopay\GoPayApiInterface $go_pay_api
   *   GoPayApi service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GoPayApiInterface $go_pay_api) {
    $this->configFactory = $config_factory;
    $this->goPayApi = $go_pay_api;
  }

  /**
   * {@inheritdoc}
   */
  public function createContact() {
    return new Contact();
  }

  /**
   * {@inheritdoc}
   */
  public function createItem() {
    return new Item();
  }

  /**
   * {@inheritdoc}
   */
  public function createStandardPayment() {
    return new StandardPayment($this->configFactory, $this->goPayApi);
  }

  /**
   * {@inheritdoc}
   */
  public function createResponseStatus($id) {
    $response = $this->goPayApi->getPaymentStatus($id);
    return new PaymentResponse($response);
  }

}
