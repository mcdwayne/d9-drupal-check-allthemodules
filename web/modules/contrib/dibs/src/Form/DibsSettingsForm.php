<?php

namespace Drupal\dibs\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DibsSettingsForm.
 *
 * @package Drupal\dibs\Form
 */
class DibsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'dibs.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dibs_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('dibs.settings');

    $form['general'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('General DIBS settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#tree' => TRUE,
    ];
    $form['general']['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#max_length' => 30,
      '#required' => TRUE,
      '#description' => $this->t('DIBS Merchant ID'),
      '#default_value' => $config->get('general.merchant_id'),
    ];
    $form['general']['account'] = [
      '#type' => 'textfield',
      '#title' => t('Account'),
      '#default_value' => $config->get('general.account'),
      '#description' => $this->t('DIBS Account ID. Only used if the DIBS gateway is running multiple accounts.'),
    ];
    $form['general']['test_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Test mode'),
      '#default_value' => $config->get('general.test_mode'),
      '#description' => $this->t('Is the gateway running in test mode'),
    ];
    $form['general']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Window type'),
      '#required' => TRUE,
      '#options' => [
        'pay' => $this->t('Pay window'),
        'flex' => $this->t('Flex window'),
        'mobile' => $this->t('Mobile window'),
      ],
      '#default_value' => $config->get('general.type'),
      '#description' => $this->t('If enabled, DIBS will make some extra checks on the sent data, to be sure that no one manipulated it. If enabled should the keys below be filled in!'),
    ];
    $form['general']['retry_handling'] = [
      '#type' => 'select',
      '#title' => $this->t('Order id handling after cancel'),
      '#options' => [
        'new_order_id' => $this->t('Generate a new order id'),
        'add_retry_suffix' => $this->t('Add retry suffix'),
      ],
      '#default_value' => $config->get('general.retry_handling'),
      '#description' => $this->t('How the order id should be handled when the user retries a cancelled payment. Some card providers (edankort) require a new order ID after cancellation.'),
    ];
    // @todo add md5 and HMAC checking.
    $form['general']['lang'] = [
      '#type' => 'select',
      '#title' => $this->t('Language'),
      '#options' => [
        'da' => 'Danish',
        'sv' => 'Swedish',
        'no' => 'Norwegian',
        'en' => 'English',
        'nl' => 'Dutch',
        'de' => 'German',
        'fr' => 'French',
        'fi' => 'Finnish',
        'es' => 'Spanish',
        'it' => 'Italian',
        'pl' => 'Polish'
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('general.lang'),
      '#description' => $this->t('Language code for the language used on the DIBS payment window'),
    ];
    $form['general']['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Currency'),
      '#options' => [
        '208' => 'Danish Kroner (DKK)',
        '978' => 'Euro (EUR)',
        '840' => 'US Dollar $ (USD)',
        '826' => 'English Pound £ (GBP)',
        '752' => 'Swedish Kronor (SEK)',
        '036' => 'Australian Dollar (AUD',
        '124' => 'Canadian Dollar (CAD)',
        '352' => 'Icelandic Króna (ISK)',
        '392' => 'Japanese Yen (JPY)',
        '554' => 'New Zealand Dollar (NZD)',
        '578' => 'Norwegian Kroner (NOK)',
        '756' => 'Swiss Franc (CHF)',
        '949' => 'Turkish Lire (TRY)',
      ],
      '#required' => TRUE,
      '#default_value' => $config->get('general.currency'),
      '#description' => $this->t('Currency code for the currency used when paying.'),
    ];

    // @todo migrate payment window settings.

    $form['paymentwindow'] = [
      '#type' => 'fieldset',
      '#title' => t('Flex Window settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    ];

    $form['paymentwindow']['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#options' => [
        'sand' => $this->t('Sand'),
        'grey' => $this->t('Grey'),
        'blue' => $this->t('Blue'),
      ],
      '#default_value' => $config->get('paymentwindow.color'),
      '#description' => $this->t('The color theme for the DIBS payment window.'),
    ];

    $form['flexwindow'] = [
      '#type' => 'fieldset',
      '#title' => t('Flex Window settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    ];

    $form['flexwindow']['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Color theme'),
      '#options' => [
        'sand' => $this->t('Sand'),
        'grey' => $this->t('Grey'),
        'blue' => $this->t('Blue'),
      ],
      '#default_value' => $config->get('flexwindow.color'),
      '#description' => $this->t('The color theme for the DIBS popup window.'),
    ];

    $form['flexwindow']['decorator'] = [
      '#type' => 'select',
      '#title' => $this->t('Decorator'),
      '#options' => [
        'default' => $this->t('Default'),
        'basal' => $this->t('Basal'),
        'rich' => $this->t('Rich'),
        'custom' => $this->t('Custom'),
      ],
      '#default_value' => $config->get('flexwindow.decorator'),
      '#description' => $this->t('Choose what DIBS decorator to use. If you want to use the one configured in the DIBS administration, please then choose "Custom".'),
    ];
    $form['flexwindow']['voucher'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Voucher'),
      '#default_value' => $config->get('flexwindow.voucher'),
      '#description' => $this->t('If set to Yes, then the list of payment types on the first page of FlexWin will contain vouchers, too.'),
    ];

    $form['mobilewindow'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Mobile Window settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    ];
    $form['mobilewindow']['payment_types'] = [
      '#type' => 'select',
      '#title' => $this->t('Select'),
      '#multiple' => TRUE,
      '#description' => $this->t('The description appears usually below the item.'),
      '#options' => array(
        'MC' => $this->t('Master Carx'),
        'VISA' => $this->t('VISA card'),
        'ELEC' => $this->t('VISA Electron'),
        'AMEX' => $this->t('American Express'),
        'DK' => $this->t('Dankort'),
        'V-DK' => $this->t('VISA/Dankort'),
      ),
      '#default_value' => $config->get('mobilewindow.payment_types'),
    ];

    $form['callbacks'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Callback URLs'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    ];
    $form['callbacks']['accept_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Accept URL'),
      '#description' => $this->t('The URL that DIBS should call after a transaction has been accepted.'),
      '#default_value' => $config->get('callbacks.accepturl'),
    ];
    $form['callbacks']['cancel_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cancel URL'),
      '#description' => $this->t('The URL that DIBS should call after a transaction has been canceled by the user.'),
      '#default_value' => $config->get('callbacks.cancelurl'),
    ];
    $form['callbacks']['callback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback URL'),
      '#description' => $this->t('The URL that DIBS should call to validate that a transaction is OK.'),
      '#default_value' => $config->get('callbacks.callback'),
    ];

    $form['advanced'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Advanced settings'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
      '#tree' => TRUE,
    ];

    $form['advanced']['calculate_fee'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Calculate fee'),
      '#description' => $this->t('If enabled, the Payment Window will automatically affix the charge due to the transaction, i.e. the charge payable to the acquirer (e.g. PBS), and display this to the customer.'),
      '#default_value' => $config->get('advanced.calculate_fee'),
    ];

    $form['advanced']['capture_now'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Capture now'),
      '#description' => $this->t('If enabled, the amount is immediately transferred from the customers account to the shop\'s account . This function can only be utilized in the event that there is no actual physical delivery of any items.'),
      '#default_value' => $config->get('advanced.capture_now'),
    ];
    $form['advanced']['unique_order_id'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Unique order ID'),
      '#description' => $this->t('If enabled, the order ID must be unique, i.e. there is no existing transaction with DIBS with the same order ID. If such a transaction already exists, payment will be rejected with reason=7.'),
      '#default_value' => $config->get('advanced.unique_order_id'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // @todo validate md5 keys and order prefixes&suffixes.
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('dibs.settings')
      ->set('general.merchant_id', $form_state->getValue('general')['merchant_id'])
      ->set('general.account', $form_state->getValue('general')['account'])
      ->set('general.test_mode', $form_state->getValue('general')['test_mode'])
      ->set('general.type', $form_state->getValue('general')['type'])
      ->set('general.lang', $form_state->getValue('general')['lang'])
      ->set('general.currency', $form_state->getValue('general')['currency'])
      ->set('general.retry_handling', $form_state->getValue('general')['retry_handling'])
      ->set('flexwindow.color', $form_state->getValue('flexwindow')['color'])
      ->set('flexwindow.decorator', $form_state->getValue('flexwindow')['decorator'])
      ->set('flexwindow.voucher', $form_state->getValue('flexwindow')['voucher'])
      ->set('mobilewindow.payment_types', $form_state->getValue('mobilewindow')['payment_types'])
      ->set('callbacks.accept_url', $form_state->getValue('callbacks')['accept_url'])
      ->set('callbacks.cancel_url', $form_state->getValue('callbacks')['cancel_url'])
      ->set('callbacks.callback', $form_state->getValue('callbacks')['callback'])
      ->set('advanced.calculate_fee', $form_state->getValue('advanced')['calculate_fee'])
      ->set('advanced.capture_now', $form_state->getValue('advanced')['capture_now'])
      ->set('advanced.unique_order_id', $form_state->getValue('advanced')['unique_order_id'])
      ->save();
  }

}
