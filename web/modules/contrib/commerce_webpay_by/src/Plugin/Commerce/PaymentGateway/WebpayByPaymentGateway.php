<?php

namespace Drupal\commerce_webpay_by\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Off-site Redirect payment Liqapy gateway.
 *
 * @CommercePaymentGateway(
 *   id = "webpay_by_gateway",
 *   label = "Webpay.by (Off-site redirect)",
 *   display_label = "Webpay.by",
 *   forms = {
 *   "offsite-payment" =
 *   "Drupal\commerce_webpay_by\PluginForm\OffsiteRedirect\WebpayByRedirectForm"
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = { "mastercard", "visa" },
 *   modes = {
 *     "sandbox" = @Translation("Sandbox"),
 *     "live" = @Translation("Live")
 *   },
 * )
 */
class WebpayByPaymentGateway extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $defaults = [
      'wsb_storeid' => '',
      'secret_key' => '',
      'wsb_return_url' => '',
      'wsb_cancel_return_url' => '',
      'wsb_currency_id' => '',
      'wsb_store' => '',
      'wsb_language_id' => 0,
    ];

    return $defaults + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['wsb_storeid'] = [
      '#title' => $this->t('Billing ID (wsb_storeid)'),
      '#description' => $this->t('This identifier is created during registration in WebPay™ and is sent in the letter.'),
      '#default_value' => $this->configuration['wsb_storeid'],
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['secret_key'] = [
      '#title' => $this->t('Secret key'),
      '#description' => $this->t('The secret key from your billing panel'),
      '#default_value' => $this->configuration['secret_key'],
      '#type' => 'textfield',
      '#required' => TRUE,
    ];

    $form['wsb_return_url'] = [
      '#title' => $this->t('Return URL (Success)'),
      '#description' => $this->t('URL to which the buyer returns in case of successful payment'),
      '#default_value' => $this->configuration['wsb_return_url'] ?: '[site:url]',
      '#type' => 'textfield',
    ];

    $form['wsb_cancel_return_url'] = [
      '#title' => $this->t('Return URL (Canceled)'),
      '#description' => $this->t('URL to which the buyer returns in case of unsuccessful payment.'),
      '#default_value' => $this->configuration['wsb_cancel_return_url'] ?: '[site:url]',
      '#type' => 'textfield',
    ];

    $form['wsb_currency_id'] = [
      '#title' => $this->t('Currency identifier'),
      '#description' => $this->t('Supported currency identifier (BYN, USD, EUR, RUB).'),
      '#default_value' => $this->configuration['wsb_currency_id'] ?: 'BYN',
      '#type' => 'select',
      '#options' => $this->availableCurrencies(),
      '#required' => TRUE,
    ];

    $form['wsb_language_id'] = [
      '#title' => $this->t('Language identifier'),
      '#description' => $this->t('Supported values are "russian" or "english". If choose "@value", the payment form will determine the language on the base of buyer\'s browser settings.', [
        '@value' => $this->t('Use browser settings'),
      ]),
      '#default_value' => $this->configuration['wsb_language_id'] ?: 0,
      '#type' => 'select',
      '#options' => $this->availableLanguages(),
      '#required' => TRUE,
    ];

    $form['wsb_store'] = [
      '#title' => $this->t('Store identifier'),
      '#description' => $this->t('This identifier is created during registration in WebPay™ and is sent in the letter.'),
      '#default_value' => $this->configuration['wsb_store'] ?: '[site:name]',
      '#type' => 'textfield',
    ];

    if (\Drupal::moduleHandler()->moduleExists('token')) {
      $form['token_help'] = [
        '#type' => 'details',
        '#title' => $this->t('Available tokens'),
      ];

      $form['token_help']['token_tree_link'] = [
        '#theme' => 'token_tree_link',
        '#token_types' => ['commerce_order'],
      ];
    }

    return $form;
  }

  /**
   * Currencies supported by API.
   *
   * @return array
   *   Currencies
   */
  public function availableCurrencies() {
    return [
      'BYN' => $this->t('BYN'),
      'USD' => $this->t('USD'),
      'EUR' => $this->t('EUR'),
      'RUB' => $this->t('RUB'),
    ];
  }

  /**
   * Language list supported by API.
   *
   * @return array
   *   Languages
   */
  public function availableLanguages() {
    return [
      0 => $this->t('Use browser settings'),
      'russian' => $this->t('Russian'),
      'english' => $this->t('English'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $values = $form_state->getValue($form['#parents']);
    $required_fields = [
      'secret_key',
      'wsb_storeid',
      'wsb_currency_id',
    ];

    foreach ($required_fields as $key) {
      if (empty($values[$key])) {
        $message = t('Service is not configured for use. Please contact an administrator to resolve this issue.');
        $this->messenger()->addError($message);
        return FALSE;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['wsb_storeid'] = $values['wsb_storeid'];
      $this->configuration['secret_key'] = $values['secret_key'];
      $this->configuration['wsb_currency_id'] = $values['wsb_currency_id'];
      $this->configuration['wsb_return_url'] = $values['wsb_return_url'];
      $this->configuration['wsb_cancel_return_url'] = $values['wsb_cancel_return_url'];
      $this->configuration['wsb_store'] = $values['wsb_store'];
      $this->configuration['wsb_language_id'] = $values['wsb_language_id'];
    }
  }

}
