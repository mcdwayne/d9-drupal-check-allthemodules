<?php

namespace Drupal\webpay\Plugin\WebpayCommerceSystem;

use Drupal\webpay\Plugin\WebpayCommerceSystemBase;
use Drupal\webpay\Entity\WebpayConfigInterface;
use Drupal\webpay\Entity\WebpayTransactionInterface;

/**
 * The test system of the webpay.
 *
 * @WebpayCommerceSystem(
 *   id = "test_webpay",
 *   label = @Translation("Test Webpay")
 * )
 */
class TestSystem extends WebpayCommerceSystemBase {

  /**
   * {@inheritdoc}
   */
  public function transactionAccepted(WebpayConfigInterface $webpay_config, WebpayTransactionInterface $transaction) {

    drupal_set_message($this->t('The transaction was accepted'));
  }

  /**
   * {@inheritdoc}
   */
  public function transactionRejected(WebpayConfigInterface $webpay_config, WebpayTransactionInterface $transaction) {

    drupal_set_message($this->t('The transaction was rejected'), 'warning');

    return parent::transactionRejected($webpay_config, $transaction);
  }
}
