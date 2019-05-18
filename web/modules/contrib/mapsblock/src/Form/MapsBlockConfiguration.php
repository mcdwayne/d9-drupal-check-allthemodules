<?php

namespace Drupal\mapsblock\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MapsBlockConfiguration.
 */
class MapsBlockConfiguration extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'mapsblock.mapsblockconfiguration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'maps_block_configuration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mapsblock.mapsblockconfiguration');
    $form['google_map_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Map API Key'),
      '#description' => $this->t('Add API key for Google Map.'),
      '#maxlength' => 64,
      '#size' => 64,
      '#required' => TRUE,
      '#default_value' => $config->get('google_map_api_key'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('mapsblock.mapsblockconfiguration')
      ->set('google_map_api_key', $form_state->getValue('google_map_api_key'))
      ->save();
  }

}
