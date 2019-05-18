<?php

namespace Drupal\data_attribute_gmap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DataAttributeGmapConfigForm.
 *
 * @package Drupal\data_attribute_gmap\Form
 */
class DataAttributeGmapConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'data_attribute_gmap_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $weight = 1;

    $form = parent::buildForm($form, $form_state);
    $config = $this->config('data_attribute_gmap.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('Google Maps API Key'),
      '#default_value' => $config->get('api_key'),
      '#description' => t(
        'A Google Maps API key is required to generate any Google Map since 22
         June 2016. Get one at https://console.developers.google.com/ and read
         the instructions in the readme file of this module.'),
      '#required' => TRUE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['width'] = [
      '#type' => 'textfield',
      '#title' => t('Default map width'),
      '#default_value' => $config->get('width'),
      '#description' => t('Map will not show if your element has no width. This
      is a fallback value in case you dont set a width via css. Examples:
      \'400\' or \'100%\' or \'400px\' or \'40rem\'.'),
      '#required' => TRUE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['height'] = [
      '#type' => 'textfield',
      '#title' => t('Default map height'),
      '#default_value' => $config->get('height'),
      '#description' => t('Map will not show if your element has no height. This
      is a fallback value in case you dont set a width via css. Examples:
      \'400\' or \'100%\' or \'400px\' or \'40rem\'.'),
      '#required' => TRUE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['marker_path'] = [
      '#type' => 'textfield',
      '#title' => t('Marker path'),
      '#default_value' => $config->get('marker_path'),
      '#description' => t('Leave empty to show default Google marker, or enter a path like: /themes/custom/mytheme/images/src/marker.png. There are some problems when using a svg (IE, Firefox), so png is advised.'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['backgroundColor'] = [
      '#type' => 'textfield',
      '#title' => t('Background Color'),
      '#default_value' => $config->get('backgroundColor'),
      '#description' => t('You can set the background color of the div while it is loading in the google map'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['center_lat'] = [
      '#type' => 'textfield',
      '#title' => t('Default map center latitude'),
      '#default_value' => $config->get('center_lat'),
      '#description' => t('Enter a latitude for the default center position of the map'),
      '#required' => TRUE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['center_long'] = [
      '#type' => 'textfield',
      '#title' => t('Default map center longitude'),
      '#default_value' => $config->get('center_long'),
      '#description' => t('Enter a longitude for the default center position of the map'),
      '#required' => TRUE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['clickableIcons'] = [
      '#type' => 'checkbox',
      '#title' => t('Clickable icons'),
      '#default_value' => $config->get('clickableIcons'),
      '#description' => t('Make icons on the map (like POIs) clickable'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['disableDefaultUI'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable default UI elements'),
      '#default_value' => $config->get('disableDefaultUI'),
      '#description' => t('Disable ALL default UI elements displayed on top of the map'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['disableDoubleClickZoom'] = [
      '#type' => 'checkbox',
      '#title' => t('Disable double click to center and zoom function'),
      '#default_value' => $config->get('disableDoubleClickZoom'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['draggable'] = [
      '#type' => 'checkbox',
      '#title' => t('Make the map draggable'),
      '#default_value' => $config->get('draggable'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['fullscreenControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the Fullscreen button'),
      '#default_value' => $config->get('fullscreenControl'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['keyboardShortcuts'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable default Google Maps keyboard shortcuts'),
      '#default_value' => $config->get('keyboardShortcuts'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['mapTypeControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Allow users to switch the map type (map, satellite, ...)'),
      '#default_value' => $config->get('mapTypeControl'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['mapTypeId'] = [
      '#type' => 'select',
      '#title' => $this->t('Displayed map type'),
      '#options' => [
        'roadmap' => 'Normal street map',
        'satellite' => 'Satellite images',
        'hybrid' => 'Hybrid: satellite with streets overlay',
        'terrain' => 'Map with physical features such as terrain and vegetation',
      ],
      '#default_value' => $config->get('mapTypeId'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['panControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the Pan control'),
      '#default_value' => $config->get('panControl'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['rotateControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the Rotate control'),
      '#default_value' => $config->get('rotateControl'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['scaleControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the Scale control'),
      '#default_value' => $config->get('scaleControl'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['scrollwheel'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable map zooming using the mouse scrollwheel'),
      '#default_value' => $config->get('scrollwheel'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['streetViewControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the Street View control (Pegman)'),
      '#default_value' => $config->get('streetViewControl'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['styles'] = [
      '#type' => 'textarea',
      '#title' => t('Map styles'),
      '#default_value' => $config->get('styles'),
      '#description' => t("Paste the 'Javascript style array' code from https://snazzymaps.com/ here if you want to have a stylized Google Map"),
      '#wysiwyg' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['zoom'] = [
      '#type' => 'number',
      '#min' => 0,
      '#max' => 18,
      '#size' => 2,
      '#title' => t('Default zoom level'),
      '#default_value' => $config->get('zoom'),
      '#description' => t('The initial Map zoom level (0 - 18)'),
      '#required' => TRUE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['zoomControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable the Zoom control'),
      '#default_value' => $config->get('zoomControl'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    $form['placesAPI'] = [
      '#type' => 'checkbox',
      '#title' => t('Include Google Places library (enable service in Google Developer Console first)'),
      '#default_value' => $config->get('placesAPI'),
      '#required' => FALSE,
      '#weight' => $weight,
    ];
    $weight++;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('data_attribute_gmap.settings');
    $config->set('api_key', $form_state->getValue('api_key'));
    $config->set('width', $form_state->getValue('width'));
    $config->set('height', $form_state->getValue('height'));
    $config->set('marker_path', $form_state->getValue('marker_path'));
    $config->set('backgroundColor', $form_state->getValue('backgroundColor'));
    $config->set('center_lat', $form_state->getValue('center_lat'));
    $config->set('center_long', $form_state->getValue('center_long'));
    $config->set('clickableIcons', $form_state->getValue('clickableIcons'));
    $config->set('disableDefaultUI', $form_state->getValue('disableDefaultUI'));
    $config->set('disableDoubleClickZoom', $form_state->getValue('disableDoubleClickZoom'));
    $config->set('draggable', $form_state->getValue('draggable'));
    $config->set('fullscreenControl', $form_state->getValue('fullscreenControl'));
    $config->set('keyboardShortcuts', $form_state->getValue('keyboardShortcuts'));
    $config->set('mapTypeControl', $form_state->getValue('mapTypeControl'));
    $config->set('mapTypeId', $form_state->getValue('mapTypeId'));
    $config->set('panControl', $form_state->getValue('panControl'));
    $config->set('rotateControl', $form_state->getValue('rotateControl'));
    $config->set('scaleControl', $form_state->getValue('scaleControl'));
    $config->set('scrollwheel', $form_state->getValue('scrollwheel'));
    $config->set('streetViewControl', $form_state->getValue('streetViewControl'));
    $config->set('styles', $form_state->getValue('styles'));
    $config->set('zoom', $form_state->getValue('zoom'));
    $config->set('zoomControl', $form_state->getValue('zoomControl'));
    $config->set('placesAPI', $form_state->getValue('placesAPI'));
    $config->save();
    // Return parent::submitForm($form, $form_state);.
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'data_attribute_gmap.settings',
    ];
  }

}
