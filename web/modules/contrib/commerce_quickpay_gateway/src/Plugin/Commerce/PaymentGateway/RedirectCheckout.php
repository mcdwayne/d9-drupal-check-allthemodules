<?php

namespace Drupal\commerce_quickpay_gateway\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Provides the QuickPay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "quickpay_redirect_checkout",
 *   label = @Translation("QuickPay (Redirect to quickpay)"),
 *   display_label = @Translation("QuickPay"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_quickpay_gateway\PluginForm\RedirectCheckoutForm",
 *   },
 * )
 */
class RedirectCheckout extends OffsitePaymentGatewayBase
{
  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'merchant_id' => '',
        'private_key' => '',
        'agreement_id' => '',
        'api_key' => '',
        'order_prefix' => '',
        'language' => 'en',
        'payment_method' => 'creditcard',
        'accepted_cards' => '',
        'autofee' => false,
        'autocapture' => false,
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('This is the Merchant ID from the Quickpay manager.'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key'),
      '#description' => $this->t('This is the private key from the Quickpay manager.'),
      '#default_value' => $this->configuration['private_key'],
      '#required' => TRUE,
    ];

    $form['agreement_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Agreement ID'),
      '#description' => $this->t('The agreement ID for the QuickPay user which will be used when going through payment. This will typically be the "Payment Window" user.'),
      '#default_value' => $this->configuration['agreement_id'],
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The API key for the same user as used in Agreement ID.'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];

    $form['order_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order ID prefix'),
      '#description' => $this->t('Prefix for order IDs. Order IDs must be uniqe when sent to QuickPay, use this to resolve clashes.'),
      '#default_value' => $this->configuration['order_prefix'],
    ];

    $languages = $this->getLanguages() + [LanguageInterface::LANGCODE_NOT_SPECIFIED => $this->t('Language of the user')];
    $form['language'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#description' => $this->t('The language for the credit card form.'),
      '#options' => $languages,
      '#default_value' => $this->configuration['language'],
    ];

    $form['payment_method'] = [
      '#type' => 'radios',
      '#id' => 'quickpay-method',
      '#title' => $this->t('Accepted payment methods'),
      '#description' => $this->t('Which payment methods to accept. NOTE: Some require special agreements.'),
      '#default_value' => $this->configuration['payment_method'],
      '#options' => [
        'creditcard' => $this->t('Creditcard'),
        '3d-creditcard' => $this->t('3D-Secure Creditcard'),
        'selected' => $this->t('Selected payment methods'),
      ],
    ];

    $options = [];
    // Add image to the cards where defined.
    foreach ($this->getQuickpayCards() as $key => $card) {
      $options[$key] = empty($card['image']) ? $card['name'] : '<img src="/' . $card['image'] . '" />' . $card['name'];
    }

    $form['accepted_cards'] = [
      '#type' => 'checkboxes',
      '#id' => 'quickpay-cards',
      '#title' => $this->t('Select accepted cards'),
      '#default_value' => $this->configuration['accepted_cards'],
      '#options' => $options,
      '#states' => [
        'visible' => [
          ':input[name="configuration[quickpay_redirect_checkout][payment_method]"]' => ['value' => 'selected'],
        ],
      ],
    ];

    $form['autofee'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autofee'),
      '#description' => $this->t('If set, the fee charged by the acquirer will be calculated and added to the transaction amount.'),
      '#default_value' => $this->configuration['autofee'],
    ];

    $form['autocapture'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Autocapture'),
      '#description' => $this->t('If set, the transactions will be automatically captured.'),
      '#default_value' => $this->configuration['autocapture'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['private_key'] = $values['private_key'];
      $this->configuration['agreement_id'] = $values['agreement_id'];
      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['order_prefix'] = $values['order_prefix'];
      $this->configuration['language'] = $values['language'];
      $this->configuration['payment_method'] = $values['payment_method'];
      $this->configuration['accepted_cards'] = $values['accepted_cards'];
      $this->configuration['autofee'] = $values['autofee'];
      $this->configuration['autocapture'] = $values['autocapture'];
    }
  }

  /**
   * Returns an array of languages supported by Quickpay.
   *
   * @return array
   *   Array with key being language codes, and value being names.
   */
  protected function getLanguages()
  {
    return [
      'da' => $this->t('Danish'),
      'de' => $this->t('German'),
      'en' => $this->t('English'),
      'fo' => $this->t('Faeroese'),
      'fr' => $this->t('French'),
      'gl' => $this->t('Greenlandish'),
      'it' => $this->t('Italian'),
      'no' => $this->t('Norwegian'),
      'nl' => $this->t('Dutch'),
      'pl' => $this->t('Polish'),
      'se' => $this->t('Swedish'),
    ];
  }

  /**
   * Information about all supported cards.
   *
   * @return array
   *   Array with card name and image.
   */
  protected function getQuickpayCards()
  {
    $images_path = drupal_get_path('module', 'commerce_quickpay_gateway') . '/images/';
    return [
      'dankort' => [
        'name' => $this->t('Dankort'),
        'image' => $images_path . 'dan.jpg',
      ],
      'visa' => [
        'name' => $this->t('Visa'),
        'image' => $images_path . 'visa.jpg',
      ],
      'visa-dk' => [
        'name' => $this->t('Visa, issued in Denmark'),
        'image' => $images_path . 'visa.jpg',
      ],
      '3d-visa' => [
        'name' => $this->t('Visa, using 3D-Secure'),
        'image' => $images_path . '3d-visa.gif',
      ],
      '3d-visa-dk' => [
        'name' => $this->t('Visa, issued in Denmark, using 3D-Secure'),
        'image' => $images_path . '3d-visa.gif',
      ],
      'visa-electron' => [
        'name' => $this->t('Visa Electron'),
        'image' => $images_path . 'visaelectron.jpg',
      ],
      'visa-electron-dk' => [
        'name' => $this->t('Visa Electron, issued in Denmark'),
        'image' => $images_path . 'visaelectron.jpg',
      ],
      '3d-visa-electron' => [
        'name' => $this->t('Visa Electron, using 3D-Secure'),
      ],
      '3d-visa-electron-dk' => [
        'name' => $this->t('Visa Electron, issued in Denmark, using 3D-Secure'),
      ],
      'mastercard' => [
        'name' => $this->t('Mastercard'),
        'image' => $images_path . 'mastercard.jpg',
      ],
      'mastercard-dk' => [
        'name' => $this->t('Mastercard, issued in Denmark'),
        'image' => $images_path . 'mastercard.jpg',
      ],
      'mastercard-debet-dk' => [
        'name' => $this->t('Mastercard debet card, issued in Denmark'),
        'image' => $images_path . 'mastercard.jpg',
      ],
      '3d-mastercard' => [
        'name' => $this->t('Mastercard, using 3D-Secure'),
      ],
      '3d-mastercard-dk' => [
        'name' => $this->t('Mastercard, issued in Denmark, using 3D-Secure'),
      ],
      '3d-mastercard-debet-dk' => [
        'name' => $this->t('Mastercard debet, issued in Denmark, using 3D-Secure'),
      ],
      '3d-maestro' => [
        'name' => $this->t('Maestro'),
        'image' => $images_path . '3d-maestro.gif',
      ],
      '3d-maestro-dk' => [
        'name' => $this->t('Maestro, issued in Denmark'),
        'image' => $images_path . '3d-maestro.gif',
      ],
      'jcb' => [
        'name' => $this->t('JCB'),
        'image' => $images_path . 'jcb.jpg',
      ],
      '3d-jcb' => [
        'name' => $this->t('JCB, using 3D-Secure'),
        'image' => $images_path . '3d-jcb.gif',
      ],
      'diners' => [
        'name' => $this->t('Diners'),
        'image' => $images_path . 'diners.jpg',
      ],
      'diners-dk' => [
        'name' => $this->t('Diners, issued in Denmark'),
        'image' => $images_path . 'diners.jpg',
      ],
      'american-express' => [
        'name' => $this->t('American Express'),
        'image' => $images_path . 'amexpress.jpg',
      ],
      'american-express-dk' => [
        'name' => $this->t('American Express, issued in Denmark'),
        'image' => $images_path . 'amexpress.jpg',
      ],
      'fbg1886' => [
        'name' => $this->t('Forbrugsforeningen'),
        'image' => $images_path . 'forbrugsforeningen.gif',
      ],
      'paypal' => [
        'name' => $this->t('PayPal'),
        'image' => $images_path . 'paypal.jpg',
      ],
      'sofort' => [
        'name' => $this->t('Sofort'),
        'image' => $images_path . 'sofort.png',
      ],
      'viabill' => [
        'name' => $this->t('ViaBill'),
        'image' => $images_path . 'viabill.png',
      ],
    ];
  }
}
