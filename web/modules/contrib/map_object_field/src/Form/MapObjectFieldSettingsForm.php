<?php
namespace Drupal\map_object_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 */
class MapObjectFieldSettingsForm extends ConfigFormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'map_object_field_settings';
  }

  /**
   * Configuration name for settings.
   *
   * @return array
   *   Settings name.
   */
  protected function getEditableConfigNames() {
    return [
      'map_object_field.settings',
    ];
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config($this->getEditableConfigNames()[0]);
    $form['google_map_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API Key'),
      '#description' => $this->t('Please, visit <a target="_blank" href="https://developers.google.com/maps/documentation/javascript/get-api-key">https://developers.google.com/maps/documentation/javascript/get-api-key</a> to get the key'),
      '#required' => TRUE,
      '#default_value' => $config->get('google_map_api_key') ? $config->get('google_map_api_key') : '',
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = \Drupal::service('config.factory')
      ->getEditable('map_object_field.settings');
    $config
      ->set('google_map_api_key', $values['google_map_api_key'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
