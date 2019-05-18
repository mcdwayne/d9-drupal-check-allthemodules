<?php

/**
 * @file
 * Contains \Drupal\robotagger_api\Form\RoboTaggerAPIAdminForm.
 */

namespace Drupal\robotagger_api\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\robotagger_api\RoboTagger;

/**
 * Configure file system settings for this site.
 */
class RoboTaggerAPIAdminForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'robotagger_api_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->configFactory->get('robotagger_api.server');
    $form['robotaggerapi_server'] = array(
      '#type' => 'textfield',
      '#title' => t('RoboTagger server host'),
      '#default_value' => RoboTagger::getHost(),
      '#required' => TRUE,
    );
    $form['robotaggerapi_server_apikey'] = array(
      '#type' => 'textfield',
      '#title' => t('RoboTagger server api-key'),
      '#default_value' => $config->get('api_key'),
      '#required' => TRUE,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
   public function validateForm(array &$form, array &$form_state) {
     if (RoboTagger::validateAPIKey($form_state['values']['robotaggerapi_server_apikey'], $form_state['values']['robotaggerapi_server']) != 1) {
       return form_set_error('robotaggerapi_server_apikey', t('Your RoboTagger api-key is not valid.'));
     }
     parent::validateForm($form, $form_state);
   }

  /**
   * {@inheritdoc}
   */
   public function submitForm(array &$form, array &$form_state) {
     $config = $this->configFactory->get('robotagger_api.server')
       ->set('api_key', $form_state['values']['robotaggerapi_server_apikey']);
     $config->save();
     parent::submitForm($form, $form_state);
   }
}
