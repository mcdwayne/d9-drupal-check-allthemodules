<?php

namespace Drupal\braintree_cashier\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SettingsForm.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'braintree_cashier.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'braintree_cashier_settings_form';
  }

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager
   *   The entity update manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('braintree_cashier.settings');

    $form['currency_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency code'),
      '#description' => $this->t('The <a href="@currency_link">currency code</a> of your default Braintree merchant account. You can see it by clicking "Test Connection" on the <a href="@api_link">Braintree API settings</a>. Defaults to <em>USD</em>. Other examples are <em>EUR</em> for Euros, <em>GBP</em> for British Pounds, <em>CAD</em> for Canadian Dollars.', [
        '@currency_link' => 'https://developers.braintreepayments.com/reference/general/currencies',
        '@api_link' => Url::fromRoute('braintree_api.braintree_api_admin_form')->toString(),
      ]),
      '#default_value' => $config->get('currency_code'),
    ];

    $form['force_locale_en'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force using the <em>en</em> locale'),
      '#description' => $this->t('This is necessary only if your web host does not have the PHP <a href="@url_intl">intl</a> extension and you have therefore run <code>composer require symfony/intl</code>. <a href="@url_symfony_intl">Symfony intl</a> requires the <em>en</em> locale and PHP 7.1.3+. Visit <a href="@php_info">PHP info</a> and search for <em>intl</em> to see if your host already has this extension.', [
        '@url_intl' => 'http://php.net/manual/en/intl.installation.php',
        '@url_symfony_intl' => 'http://symfony.com/doc/current/components/intl.html',
        '@php_info' => Url::fromRoute('system.php')->toString(),
      ]),
      '#default_value' => $config->get('force_locale_en'),
    ];

    $form['free_trial_notification_period'] = [
      '#type' => 'number',
      '#min' => '0',
      // Max is needed to avoid sending free trial expiration notifications
      // more than once, due to the key-value expiration in
      // \Drupal\braintree_cashier\Plugin\QueueWorker\RetrieveExpiringFreeTrials::processItem.
      '#max' => '28',
      '#title' => $this->t('Free trial notification period'),
      '#field_suffix' => $this->t('days'),
      '#description' => $this->t("The number of days in advance to send an email notification to a user when their free trial is about to expire. Enter zero for no notification. If you're using free trials, this should be set to a non-zero value to avoid <a href='@trial_docs_url'>negative option billing</a>", [
        '@trial_docs_url' => 'https://articles.braintreepayments.com/guides/recurring-billing/trial-periods',
      ]),
      '#default_value' => $config->get('free_trial_notification_period'),
    ];

    $form['prevent_duplicate_payment_methods'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Prevent duplicate payment methods'),
      '#description' => $this->t('Different accounts may not use the same credit card or PayPal account as a payment method. This attempts to reduce the frequency of multiple free trials by a single individual.'),
      '#default_value' => $config->get('prevent_duplicate_payment_methods'),
    ];

    $form['accept_paypal'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Accept PayPal as a payment method'),
      '#default_value' => $config->get('accept_paypal'),
    ];

    $form['enable_coupon_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable coupon code field on signup form'),
      '#default_value' => $config->get('enable_coupon_field'),
    ];

    $form['duplicate_payment_method_message'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Duplicate payment method error message'),
      '#description' => $this->t('Presented to users when they attempt to use a credit card or PayPal account already in use by another user.'),
      '#default_value' => $config->get('duplicate_payment_method_message'),
    ];

    $form['invoice_business_information'] = [
      '#type' => 'text_format',
      '#format' => empty($config->get('invoice_business_information')['format']) ? NULL : $config->get('invoice_business_information')['format'],
      '#title' => $this->t('Invoice business information'),
      '#description' => $this->t('Business information to display on invoices, such as the business address.'),
      '#default_value' => $config->get('invoice_business_information')['value'],
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable additional logging for debugging'),
      '#default_value' => $config->get('debug'),
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $values = $form_state->getValues();
    $currencies = new ISOCurrencies();
    $currency = new Currency(strtoupper($values['currency_code']));
    if (!$currency->isAvailableWithin($currencies)) {
      $message = $this->t('Not a valid currency code.');
      $form_state->setErrorByName('currency_code', $message);
      $this->logger->error($message);
    }
    $form_state->setValue('currency_code', strtoupper($values['currency_code']));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('braintree_cashier.settings');
    $values = $form_state->getValues();
    $keys = [
      'currency_code',
      'invoice_business_information',
      'force_locale_en',
      'free_trial_notification_period',
      'prevent_duplicate_payment_methods',
      'accept_paypal',
      'enable_coupon_field',
      'duplicate_payment_method_message',
      'debug',
    ];
    foreach ($keys as $key) {
      if (isset($values[$key])) {
        $config->set($key, $values[$key]);
      }
    }
    $config->save();
  }

}
