<?php

namespace Drupal\googlemap_block\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Google Map Setting.
 */
class GoogleMapSetting extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_map_setting';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'googlemap_block.settings',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * Implements admin settings form.
   *
   * @param array $form
   *   From render array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('googlemap_block.settings');
    $form['map'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Settings'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['map']['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google API key'),
      '#default_value' => $config->get('api_key') ? $config->get('api_key') : '',
      '#required' => TRUE,
      '#description' => $this->t('To create a new key, visit <a href="@api-url">https://developers.google.com/maps/documentation/javascript/get-api-key</a>', ['@api-url' => 'https://developers.google.com/maps/documentation/javascript/get-api-key']),
    ];
    $form['map']['map_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Height'),
      '#default_value' => $config->get('map_height') ? $config->get('map_height') : '500px',
    ];
    $form['map']['map_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Widht'),
      '#default_value' => $config->get('map_width') ? $config->get('map_width') : '500px',
    ];
    $form['map']['map_zoom_level'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Zoom Level'),
      '#default_value' => $config->get('map_zoom_level') ? $config->get('map_zoom_level') : 2,
    ];
    $form['map']['map_center_position'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Center Position'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['map']['map_center_position']['lat'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Center latitude'),
      '#default_value' => $config->get('lat') ? $config->get('lat') : 38.963745,
    ];
    $form['map']['map_center_position']['long'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Center longitude'),
      '#default_value' => $config->get('long') ? $config->get('long') : 35.243322,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('googlemap_block.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('map_height', $form_state->getValue('map_height'))
      ->set('map_width', $form_state->getValue('map_width'))
      ->set('map_zoom_level', $form_state->getValue('map_zoom_level'))
      ->set('lat', $form_state->getValue('lat'))
      ->set('long', $form_state->getValue('long'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
