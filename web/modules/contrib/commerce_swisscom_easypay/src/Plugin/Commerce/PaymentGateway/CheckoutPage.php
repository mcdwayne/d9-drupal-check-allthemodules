<?php

namespace Drupal\commerce_swisscom_easypay\Plugin\Commerce\PaymentGateway;

use Drupal;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_swisscom_easypay\CheckoutPageResponseService;
use Drupal\Core\Form\FormStateInterface;
use Gridonic\EasyPay\Environment\Environment;
use Gridonic\EasyPay\REST\RESTApiService;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Swisscom Easypay CheckoutPage payment gateway.
 *
 * @package Drupal\commerce_swisscom_easypay\Plugin\Commerce\PaymentGateway
 *
 * @CommercePaymentGateway(
 *   id = "swisscom_easypay_checkoutpage",
 *   label = @Translation("Swisscom Easypay (Checkout Page)"),
 *   display_label = @Translation("Swisscom Easypay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_swisscom_easypay\PluginForm\CheckoutPageForm",
 *   }
 * )
 */
class CheckoutPage extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'secret' => '',
      'checkout_page_title' => '',
      'checkout_page_description' => '',
      'checkout_page_image_url' => '',
      'payment_info' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $formState) {
    $form = parent::buildConfigurationForm($form, $formState);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $this->configuration['secret'],
      '#required' => TRUE,
    ];

    $form['checkout_page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Checkout page title'),
      '#default_value' => $this->configuration['checkout_page_title'],
      '#description' => $this->t('The title displayed on the checkout page'),
      '#required' => TRUE,
    ];

    $form['checkout_page_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Checkout page description'),
      '#default_value' => $this->configuration['checkout_page_description'],
      '#description' => $this->t('A description displayed on the checkout page. If empty, a summary of the cart is displayed'),
    ];

    $form['checkout_page_image_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Checkout page image'),
      '#default_value' => $this->configuration['checkout_page_image_url'],
      '#description' => $this->t('Absolute URL to an image which will be presented on the checkout page'),
    ];

    $form['payment_info'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payment information for customer'),
      '#default_value' => $this->configuration['payment_info'],
      '#description' => $this->t('The payment info of the service, which will be printed on the bill of the customer'),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $formState) {
    parent::submitConfigurationForm($form, $formState);

    $values = $formState->getValue($form['#parents']);

    $this->configuration['merchant_id'] = $values['merchant_id'];
    $this->configuration['secret'] = $values['secret'];
    $this->configuration['checkout_page_title'] = $values['checkout_page_title'];
    $this->configuration['checkout_page_description'] = $values['checkout_page_description'];
    $this->configuration['checkout_page_image_url'] = $values['checkout_page_image_url'];
    $this->configuration['payment_info'] = $values['payment_info'];
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);

    $checkoutPageResponseService = $this->getCheckoutPageResponseService();
    $checkoutPageResponseService->onReturn($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);

    $checkoutPageResponseService = $this->getCheckoutPageResponseService();
    $checkoutPageResponseService->onCancel($order, $request);
  }

  /**
   * Get the entity ID.
   *
   * @return string
   *   The payment gateway entity ID.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Get the Easypay prod or staging environment based on the plugin config.
   *
   * @return \Gridonic\EasyPay\Environment\Environment
   *   Prod or stating environment depending on configuration.
   */
  protected function getEnvironment() {
    $config = $this->configuration;
    $type = ($config['mode'] === 'live') ? Environment::ENV_PROD : Environment::ENV_STAGING;

    return new Environment($type, $config['merchant_id'], $config['secret']);
  }

  /**
   * Return the CheckoutPageResponseService.
   *
   * @return \Drupal\commerce_swisscom_easypay\CheckoutPageResponseService
   *   The CheckoutPageResponseService.
   */
  protected function getCheckoutPageResponseService() {
    return new CheckoutPageResponseService(
      $this,
      $this->entityTypeManager,
      Drupal::service('logger.factory')->get('commerce_swisscom_easypay'),
      RESTApiService::create($this->getEnvironment())
    );
  }

}
