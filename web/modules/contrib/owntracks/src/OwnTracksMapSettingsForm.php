<?php

namespace Drupal\owntracks;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure owntracks_location settings.
 */
class OwnTracksMapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'owntracks.map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'owntracks_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['tile_layer_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tile layer url'),
      '#default_value' => $this->config('owntracks.map.settings')->get('tileLayerUrl'),
      '#required' => TRUE,
    ];

    $form['tile_layer_attribution'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tile layer attribution'),
      '#default_value' => $this->config('owntracks.map.settings')->get('tileLayerAttribution'),
      '#required' => TRUE,
    ];

    $form['polyline_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Polyline color'),
      '#default_value' => $this->config('owntracks.map.settings')->get('polylineColor'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('owntracks.map.settings')
      ->set('tileLayerUrl', $form_state->getValue('tile_layer_url'))
      ->set('tileLayerAttribution', $form_state->getValue('tile_layer_attribution'))
      ->set('polylineColor', $form_state->getValue('polyline_color'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
