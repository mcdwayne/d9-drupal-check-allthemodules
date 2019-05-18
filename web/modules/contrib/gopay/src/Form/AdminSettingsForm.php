<?php

namespace Drupal\gopay\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\gopay\GoPayApiInterface;

/**
 * Configure example settings for this site.
 */
class AdminSettingsForm extends ConfigFormBase {

  /**
   * GoPayApi service.
   *
   * @var \Drupal\gopay\GoPayApiInterface
   */
  protected $goPayApi;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   ConfigFactory service.
   * @param \Drupal\gopay\GoPayApiInterface $go_pay_api
   *   GoPayApi service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, GoPayApiInterface $go_pay_api) {
    parent::__construct($config_factory);
    $this->goPayApi = $go_pay_api;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('gopay.api')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gopay_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gopay.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gopay.settings');

    $form['oauth2'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('OAuth2 settings'),
      '#open' => TRUE,
    ];

    $form['oauth2']['go_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Go ID'),
      '#default_value' => $config->get('go_id'),
      '#required' => TRUE,
    ];

    $form['oauth2']['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#default_value' => $config->get('client_id'),
      '#required' => TRUE,
    ];

    $form['oauth2']['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client secret'),
      '#default_value' => $config->get('client_secret'),
      '#required' => TRUE,
    ];

    $form['oauth2']['production_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Is production mode'),
      '#default_value' => $config->get('production_mode'),
    ];

    $form['payer'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Payment settings'),
      '#open' => TRUE,
    ];

    $form['payer']['allowed_payment_instruments'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Payment methods'),
      '#default_value' => $config->get('allowed_payment_instruments'),
      '#options' => $this->goPayApi->getPaymentInstruments(),
      '#required' => TRUE,
    ];

    $form['payer']['default_payment_instrument'] = [
      '#type' => 'select',
      '#title' => $this->t('Default payment method'),
      '#default_value' => $config->get('default_payment_instrument'),
      '#options' => $this->goPayApi->getPaymentInstruments(),
      '#required' => TRUE,
    ];

    $form['callbacks'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Callbacks'),
      '#open' => TRUE,
    ];

    $form['callbacks']['return_callback'] = [
      '#type' => 'url',
      '#title' => $this->t('Return callback'),
      '#default_value' => $config->get('return_callback'),
    ];

    $form['callbacks']['notification_callback'] = [
      '#type' => 'url',
      '#title' => $this->t('Notification callback'),
      '#default_value' => $config->get('notification_callback'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable('gopay.settings')
      ->set('go_id', $form_state->getValue('go_id'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('production_mode', $form_state->getValue('production_mode'))
      ->set('allowed_payment_instruments', $form_state->getValue('allowed_payment_instruments'))
      ->set('default_payment_instrument', $form_state->getValue('default_payment_instrument'))
      ->set('return_callback', $form_state->getValue('return_callback'))
      ->set('notification_callback', $form_state->getValue('notification_callback'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
