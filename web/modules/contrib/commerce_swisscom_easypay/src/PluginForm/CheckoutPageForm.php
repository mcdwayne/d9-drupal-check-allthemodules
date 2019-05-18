<?php

namespace Drupal\commerce_swisscom_easypay\PluginForm;

use Drupal;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_swisscom_easypay\CheckoutPageService;
use Drupal\Core\Form\FormStateInterface;

/**
 * A form redirecting to the Swisscom Easypay checkout page.
 *
 * @package Drupal\commerce_postfinance\PluginForm
 */
class CheckoutPageForm extends PaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form = parent::buildConfigurationForm($form, $formState);

    /* @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    $order = $payment->getOrder();

    /** @var \Drupal\commerce_swisscom_easypay\Plugin\Commerce\PaymentGateway\CheckoutPage $checkoutPage */
    $checkoutPage = $payment->getPaymentGateway()->getPlugin();
    $config = $checkoutPage->getConfiguration();

    $checkoutPageService = new CheckoutPageService($config, Drupal::service('event_dispatcher'), Drupal::service('language_manager'));
    $redirectUrl = $checkoutPageService->getCheckoutPageUrl($order, $form);

    return $this->buildRedirectForm(
      $form,
      $formState,
      $redirectUrl,
      [],
      PaymentOffsiteForm::REDIRECT_POST
    );
  }

}
