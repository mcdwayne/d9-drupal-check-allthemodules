<?php

namespace Drupal\webpay\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\webpay\Entity\WebpayConfigInterface;
use Drupal\webpay\Entity\WebpayTransactionInterface;

/**
 * Defines an interface for Webpay commerce system plugins.
 */
interface WebpayCommerceSystemInterface extends PluginInspectionInterface {

  /**
   * This method is invoked when webpay accept the transaction.
   *
   * Here you can finish a local transaction of the commerce system.
   *
   * @param WebpayConfigInterface $config
   *   The configuration of the commerce code.
   * @param WebpayTransactionInterface $webpay_transaction
   *   The WebpayTransaction object.
   */
  public function transactionAccepted(WebpayConfigInterface $webpay_config, WebpayTransactionInterface $transaction);

  /**
   * This method is invoked when webpay reject the transaction.
   *
   * Here you can cancel a local transaction of the commerce system.
   *
   * @param WebpayConfigInterface $config
   *   The configuration of the commerce code.
   * @param WebpayTransactionInterface $webpay_transaction
   *   The WebpayTransaction object.
   *
   * @return Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function transactionRejected(WebpayConfigInterface $webpay_config, WebpayTransactionInterface $transaction);
}
