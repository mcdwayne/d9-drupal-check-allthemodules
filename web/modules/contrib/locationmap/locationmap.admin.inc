<?php

/**
 * Callback for admin settings page
 */
function locationmap_admin_settings($form, &$form_state) {

  $config = config('locationmap.settings');

  $path = drupal_get_path('module', 'locationmap');
  $locationmap_admin_css_options = array(
    'preprocess'  => FALSE,
  );

  drupal_add_css($path . '/locationmap_admin.css', $locationmap_admin_css_options);

  $defaults = array(
    'value' => '',
    'format' => filter_default_format(),
  );

  drupal_add_js($path . '/locationmap_admin.js');

  $form['info'] = array(
    '#type' => 'fieldset',
    '#title' => t('Location information'),
    '#collapsible' => FALSE,
  );

  $form['info']['title'] = array(
    '#type' => 'textfield',
    '#title' => t('Title'),
    '#default_value' => $config->get('title'),
    '#description' => t("The title of the automatically generated ") . l(t('map page'), 'locationmap') . '.',
  );

  $form['info']['address'] = array('#type' => 'textfield',
    '#required' => TRUE,
    '#title' => t('Address of your location'),
    '#default_value' => $config->get('address'),
    '#description' => t('Enter your address separated by commas. This will be sent to Google for geocoding to determine the geographical coordinates of your location. Include any suitable from: # Street, Suburb, City, Region/State, Postcode/Zip, Country.'),
  );

  $form['info']['type'] = array(
    '#type' => 'select',
    '#title' => t('Map type'),
    '#default_value' => $config->get('type'),
    '#description' => NULL,
    '#options' => array(
      'google.maps.MapTypeId.ROADMAP' => 'the default view',
      'google.maps.MapTypeId.SATELLITE' => 'showing Google Earth satellite images',
      'google.maps.MapTypeId.HYBRID' => 'showing a mixture of normal and satellite views',
      'google.maps.MapTypeId.TERRAIN' => 'showing a physical map based on terrain information',
    ),
  );

  $zoom_levels = array('0' => t('0 - minimum zoom level, whole world'));
  for ($i = 1; $i < 17; $i++) {
    $zoom_levels["$i"] = "$i";
  }
  $zoom_levels['17'] = t('17 - maximum zoom level');

  $form['info']['zoom'] = array('#type' => 'select',
    '#title' => t('Map zoom level'),
    '#default_value' => $config->get('zoom'),
    '#description' => NULL,
    '#options' => $zoom_levels,
  );

  $form['info']['width'] = array('#type' => 'textfield',
    '#title' => t('Map Width'),
    '#default_value' => $config->get('width'),
    '#field_suffix' => 'px',
    '#description' => NULL,
    '#size' => 10,
  );

  $form['info']['height'] = array('#type' => 'textfield',
    '#title' => t('Map Height'),
    '#default_value' => $config->get('height'),
    '#field_suffix' => 'px',
    '#description' => NULL,
    '#size' => 10,
  );

  $form['info']['latlng'] = array(
    '#type' => 'fieldset',
    '#title' => t('Geographical coordinates'),
    '#collapsible' => FALSE,
    '#description' => t('Geographical coordinates for your location. Location map will try to obtain this information from Google using the address above. You are also able to fine-tune this by dragging the marker on the' . ' ' . l(t('map page'), 'locationmap') . '. Under normal circumstances you would not set these coordinates manually.'),
  );

  $form['info']['latlng']['latitude'] = array(
    '#type' => 'textfield',
    '#title' => t('Latitude'),
    '#default_value' => $config->get('latitude'),
  );

  $form['info']['latlng']['longitude'] = array(
    '#type' => 'textfield',
    '#title' => t('Longitude'),
    '#default_value' => $config->get('longitude'),
  );

  $form['info']['info'] = array(
    '#type' => 'text_format',
    '#title' => t('Marker Information'),
    '#default_value' => $config->get('info.value'),
    '#format' => $config->get('info.format'),
    '#description' => t('The description that will be shown when a user clicks on the marker. If this field is empty, the address will be used.'),
  );

  $form['info']['body'] = array(
    '#type' => 'text_format',
    '#title' => t('Additional information (displayed above map)'),
    '#required' => FALSE,
    '#default_value' => $config->get('body.value'),
    '#format' => $config->get('body.format'),
    '#description' => t('Any additional information that you would like to include above the map.'),
  );

  $form['info']['footer'] = array(
    '#type' => 'text_format',
    '#title' => t('Additional information (displayed below map)'),
    '#required' => FALSE,
    '#default_value' => $config->get('footer.value'),
    '#format' => $config->get('footer.format'),
    '#description' => t('Any additional information you would like to include below the map.'),
  );

  $form['#validate'][] = 'locationmap_admin_settings_validate';
  $form['#submit'][] = 'locationmap_admin_settings_submit';

  return system_config_form($form, $form_state);
}

/**
 * @todo Please document this function.
 * @see http://drupal.org/node/1354
 */
function locationmap_admin_settings_validate($form, &$form_state) {

  $config = config('locationmap.settings');

  if ($form_state['values']['address']) {
    if ($form_state['values']['address'] == $config->get('address')) {
      drupal_set_message('Address has not changed. In order to preserve any location marker fine-tuning, coordinates have not been updated.', 'status');
      return;
    }
    $latlng = locationmap_geocode_for_address_recursive($form_state['values']['address']);
    if ($latlng) {
      list($lat, $lng) = $latlng;
      $form_state['values']['latitude'] = $lat;
      $form_state['values']['longitude'] = $lng;
    }
    else {
      switch ($form_state['values']['latitude']) {
        // If Geocoding fails and there are no previous coordinates, set to the default: Round Island in New Zealand's Fiordland National Park.
        case '':
          $form_state['values']['latitude'] = '-46.0868686';
          $form_state['values']['longitude'] = '166.6822074';
          drupal_set_message('Unable to Geocode address provided. As no previous coordinates were recorded, default values have been inserted. Try slightly modifying the address and resubmit, e.g. giving more or less detail, such as leaving out the suburb or state.', 'warning');
          break;
        // If Geocoding fails and there are previous coordinates, set to those coordinates.
        default:
          $form_state['values']['latitude'] = $config->get('latitude');
          $form_state['values']['longitude'] = $config->get('longitude');
          drupal_set_message('Unable to Geocode address provided&mdash;previous coordinates will be used. Try slightly modifying the address and resubmit, e.g. giving more detail, or leaving out the suburb or state.', 'warning');
          break;
      }
    }
  }
}

/**
 * Submit function for locationmap_admin_settings
 * @param $form
 * @param $form_state
 */

function locationmap_admin_settings_submit($form, $form_state) {

  $config = config('locationmap.settings');
  $config
    ->set('title', $form_state['values']['title'])
    ->set('address', $form_state['values']['address'])
    ->set('type', $form_state['values']['type'])
    ->set('zoom', $form_state['values']['zoom'])
    ->set('width', $form_state['values']['width'])
    ->set('height', $form_state['values']['height'])
    ->set('latitude', $form_state['values']['latitude'])
    ->set('longitude', $form_state['values']['longitude'])
    ->set('info.value', $form_state['values']['info']['value'])
    ->set('info.format', $form_state['values']['info']['format'])
    ->set('body.value', $form_state['values']['body']['value'])
    ->set('body.format', $form_state['values']['body']['format'])
    ->set('footer.value', $form_state['values']['footer']['value'])
    ->set('footer.format', $form_state['values']['footer']['format'])
    ->save();
}