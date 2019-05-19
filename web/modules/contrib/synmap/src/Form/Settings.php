<?php

namespace Drupal\synmap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements the form controller.
 */
class Settings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'synmap_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['synmap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('synmap.settings');

    $form['#attached']['library'][] = 'synmap/form';
    $form['#attached']['drupalSettings']['synmap']['data'] = [
      'latitude'  => $config->get('map-latitude')?:39.858191,
      'longitude' => $config->get('map-longitude')?:59.214189,
      'zoom'      => $config->get('map-zoom')?:16,
      'name'      => $config->get('yamap-name')?:$this->t('Synapse'),
      'attach'    => $config->get('yamap-attach'),
      'offset_x'  => $config->get('map-offset_x'),
      'offset_y'  => $config->get('map-offset_y'),
      'editlatitude'  => '#edit-map-latitude',
      'editlongitude' => '#edit-map-longitude',
    ];

    $form['yamap'] = [
      '#type' => 'details',
      '#title' => $this->t('Map main'),
      '#open' => TRUE,
    ];

    $form['yamap']['yamap-name'] = [
      '#title' => $this->t('Company Name'),
      '#default_value' => $config->get('yamap-name'),
      '#maxlength' => 255,
      '#size' => 80,
      '#required' => TRUE,
      '#type' => 'textfield',
      '#description' => $this->t('The text that is displayed when the pointer is clicked'),
    ];
    $form["yamap"]["yamap-enable"] = [
      '#title' => t('Enable map'),
      '#type' => 'radios',
      '#options' => [
        'enable' => t('Whole site enable'),
        'enable_contact' => t('Contact page only'),
        'disable' => t('Disable'),
      ],
      '#default_value' => $config->get('yamap-enable'),
      '#description' => '',
      '#required' => TRUE,
    ];
    $form["yamap"]["yamap-path"] = [
      '#title' => t("Attachment page path"),
      '#type' => 'textfield',
      '#default_value' => $config->get('yamap-path'),
      '#description' => t('Example: /contact/feedback'),
    ];
    $form["yamap"]["yamap-attach"] = [
      '#title' => t("Attach to"),
      '#type' => 'textfield',
      '#default_value' => $config->get('yamap-attach'),
      '#description' => t('Map place $.before(), example: `.region.region-content`'),
    ];
    // Geoposition.
    $form['geo'] = [
      '#type' => 'details',
      '#title' => $this->t('Map extra'),
      '#open' => FALSE,
    ];
    $form["geo"]["map-latitude"] = [
      '#title' => t("Latitude"),
      '#type' => 'textfield',
      '#default_value' => $config->get('map-latitude'),
      '#description' => t('Example: 39.858191'),
    ];
    $form["geo"]["map-longitude"] = [
      '#title' => t("Longitude"),
      '#type' => 'textfield',
      '#default_value' => $config->get('map-longitude'),
      '#description' => t('Example: 59.214189'),
    ];
    $form["geo"]["map-zoom"] = [
      '#title' => t("Zoom"),
      '#type' => 'textfield',
      '#default_value' => $config->get('map-zoom'),
      '#description' => t('Example: 16'),
    ];
    $form["geo"]["map-offset_x"] = [
      '#title' => t("Map center Offset X ↔"),
      '#type' => 'textfield',
      '#default_value' => $config->get('map-offset_x'),
      '#description' => t('Example 0.0005'),
    ];
    $form["geo"]["map-offset_y"] = [
      '#title' => t("Map center Offset Y ↕"),
      '#type' => 'textfield',
      '#default_value' => $config->get('map-offset_y'),
      '#description' => t('Example 0.0005'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements a form submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('synmap.settings');
    $config
      ->set('yamap-name', $form_state->getValue('yamap-name'))
      ->set('yamap-enable', $form_state->getValue('yamap-enable'))
      ->set('yamap-path', $form_state->getValue('yamap-path'))
      ->set('yamap-attach', $form_state->getValue('yamap-attach'))
      ->set('map-latitude', $form_state->getValue('map-latitude'))
      ->set('map-longitude', $form_state->getValue('map-longitude'))
      ->set('map-zoom', $form_state->getValue('map-zoom'))
      ->set('map-offset_x', $form_state->getValue('map-offset_x'))
      ->set('map-offset_y', $form_state->getValue('map-offset_y'))
      ->save();
  }

}
