<?php

namespace Drupal\opencalais_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Configure global opencalais settings.
 */
class GeneralSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'opencalais_ui_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'opencalais_ui.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    $config = $this->config('opencalais_ui.settings');
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('Open Calais API Key'),
      '#default_value' => $config->get('api_key'),
      '#size' => 60,
      '#description' => t('Required to utilize the Calais service. Click <a href=":key_page">here</a> to get one.', [
        ':key_page' => Url::fromUri('https://iameui-eagan-prod.thomsonreuters.com/iamui/UI/createUser?app_id=Bold&realm=Bold')
          ->toString()
      ])
    ];

    $form['api_server'] = [
      '#type' => 'textfield',
      '#title' => t('Open Calais Server'),
      '#default_value' => $config->get('api_server') ?: 'api.thomsonreuters.com',
      '#size' => 60,
      '#description' => t('The domain name for the server to use. Typically you will not have to change this unless you want to test beta functionality.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('opencalais_ui.settings');
    $keys = [
      'api_key',
      'api_server',
    ];
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
