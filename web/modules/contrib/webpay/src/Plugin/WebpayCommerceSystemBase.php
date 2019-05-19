<?php

namespace Drupal\webpay\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\webpay\Entity\WebpayConfigInterface;
use Drupal\webpay\Entity\WebpayTransactionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Base class for Webpay commerce system plugins.
 */
abstract class WebpayCommerceSystemBase extends PluginBase implements WebpayCommerceSystemInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function transactionRejected(WebpayConfigInterface $webpay_config, WebpayTransactionInterface $transaction) {

    return new RedirectResponse(\Drupal::url('webpay.webpay_failure', ['token' => $transaction->get('token')->value], [
      'absolute' => TRUE,
    ]));
  }
}
