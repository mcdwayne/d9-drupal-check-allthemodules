<?php

namespace Drupal\janrain_connect_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Form for configure messages.
 */
class JanrainConnectUiConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'janrain_connect_config_ajax';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'janrain_connect.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('janrain_connect.settings');

    $description = $this->t('Use the Yaml format.');
    $description = '<a href="https://www.drupal.org/docs/8/modules/drupal-connector-for-janrain-identity-cloud/config-forms" target="blank">' . $this->t('Check this example') . '</a>';

    $form['configuration_forms'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Configuration'),
      '#default_value' => $config->get('configuration_forms'),
      '#description' => $description,
      '#rows' => 20,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $configuration_forms = $form_state->getValue('configuration_forms');

    try {
      Yaml::parse($configuration_forms, Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE);
    }
    catch (\Exception $e) {
      $form_state->setErrorByName('configuration_forms', $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('janrain_connect.settings');

    $configuration_forms = $form_state->getValue('configuration_forms');

    $config->set('configuration_forms', $configuration_forms);

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
