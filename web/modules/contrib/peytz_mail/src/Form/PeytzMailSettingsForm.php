<?php

namespace Drupal\peytz_mail\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\peytz_mail\PeytzMailer;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents the admin settings form for Peytz Mail.
 */
class PeytzMailSettingsForm extends ConfigFormBase {


  protected $peytzMailer;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, PeytzMailer $peytz_mailer) {
    parent::__construct($config_factory);
    $this->peytzMailer = $peytz_mailer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('peytz_mail.peytzmailer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'peytz_mail_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['peytz_mail.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('peytz_mail.settings');

    $form['service_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service URL'),
      '#description' => $this->t('URL of Peytz mail service that will be used as a base while accessing the service.'),
      '#default_value' => $config->get('service_url'),
      '#required' => TRUE,
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Key'),
      '#description' => $this->t('Key used as access credential for the service.'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    ];

    $form['secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret'),
      '#description' => $this->t('A token used to validate unsubscribe requests that ensures security.'),
      '#default_value' => $config->get('secret'),
      '#required' => TRUE,
    ];

    $form['debug'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug'),
      '#description' => $this->t('Whether to access the service in debug mode.'),
      '#default_value' => $config->get('debug'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $submitted_values = $form_state->getValues();

    $config = $this->config('peytz_mail.settings');

    if ($config->get('service_url') !== $submitted_values['service_url']
    || $config->get('api_key') !== $submitted_values['api_key']) {
      $this->peytzMailer->setSettings($submitted_values['service_url'], $submitted_values['api_key']);
    }

    // Check Peytz mail status.
    $result = $this->peytzMailer->checkStatus($submitted_values['service_url']);
    if ($result !== TRUE) {
      drupal_set_message($this->t('Validation failed. @msg', ['@msg' => $result]), 'error');
      $form_state->setErrorByName('service_url', $this->t('Check for correct service url'));
      $form_state->setErrorByName('api_key', $this->t('Check for correct API key'));
      return;
    }

    // Check settings, see if we can get mailinglists.
    $result = $this->peytzMailer->checkSettings();
    if ($result !== TRUE) {
      $form_state->setErrorByName('service_url', $this->t('Validation failed, can not get any mailinglists. Please check if you setup the correct service URL. @msg',
        ['@msg' => $result]));
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $submitted_values = $form_state->getValues();

    $this->config('peytz_mail.settings')
      ->set('service_url', $submitted_values['service_url'])
      ->set('api_key', $submitted_values['api_key'])
      ->set('secret', $submitted_values['secret'])
      ->set('debug', $submitted_values['debug'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
