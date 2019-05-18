<?php

namespace Drupal\recurly_aegir\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Recurly Aegir configuration settings form.
 */
class RecurlyAegirSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'recurly_aegir_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['recurly_aegir.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('recurly_aegir.settings');

    $form['service_endpoint_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Service endpoint URL'),
      '#description' => $this->t("The location of your Aegir service. HTTPS is recommended to prevent key disclosure.  As a security precaution, the hostname must be included in <em>\$settings['trusted_host_patterns']</em> in your settings(.local).php."),
      '#default_value' => $config->get('service_endpoint_url'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('https://aegir.example.com/aegir/saas'),
      ],
    ];

    $form['service_endpoint_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#description' => $this->t('The key necessary to access the service endpoint. Defined in your Aegir configuration under Administration » Structure » Services » hosting_saas » Authentication.'),
      '#default_value' => $config->get('service_endpoint_key'),
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => $this->t('super-secret-random-key'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (!UrlHelper::isValid($form_state->getValue(['service_endpoint_url']), TRUE)) {
      $form_state->setErrorByName('service_endpoint_url', $this->t('The URL is not valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('recurly_aegir.settings')
      ->set('service_endpoint_url', $form_state->getValue('service_endpoint_url'))
      ->set('service_endpoint_key', $form_state->getValue('service_endpoint_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
