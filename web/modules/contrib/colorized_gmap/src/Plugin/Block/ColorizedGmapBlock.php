<?php

namespace Drupal\colorized_gmap\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\SettingsCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Provides a 'Example: configurable text string' block.
 *
 * Drupal\Core\Block\BlockBase gives us a very useful set of basic functionality
 * for this configurable block. We can just fill in a few of the blanks with
 * defaultConfiguration(), blockForm(), blockSubmit(), and build().
 *
 * @Block(
 *   id = "colorized_gmap",
 *   admin_label = @Translation("Colorized Google Map"),
 * )
 */

class ColorizedGmapBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {

    // Check Google API Key.
    $this->checkApiKey();

    $form['#attached']['library'][] = 'colorized_gmap/colorized_gmap.gmap_api';
    $form['#attached']['library'][] = 'colorized_gmap/colorized_gmap.block_admin';
    $form['#attached']['drupalSettings'] = $this->getFormattedJsMapAdminSettings();

    // Build long settings form.
    $this->buildFormStylers($form, $form_state);
    $this->buildFormCoordinates($form, $form_state);

    $form['additional_settings'] = [
      '#type' => 'fieldset',
      '#tree' => TRUE,
      '#title' => t('Additional gmap api settings'),
      '#weight' => 4,
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    ];

    $this->buildFormZoom($form, $form_state);
    $this->buildFormControls($form, $form_state);
    $this->buildFormControlsPosition($form, $form_state);
    $this->buildFormMarker($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'coordinates' => [
        'latitude' => '48.853358',
        'longitude' => '2.348903',
      ],
      'colorized_map_styles' => [],
      'additional_settings' => [
        'controls' => [
          'min_drag_width' => 0,
          'streetViewControl' => TRUE,
          'panControl' => TRUE,
          'mapTypeControl' => TRUE,
        ],
        'controls_position' => [
          'streetViewControl' => '1',
          'panControl' => '1',
          'mapTypeControl' => '3',
        ],
        'zoom_controls' => [
          'zoom' => '15',
          'zoomControl' => TRUE,
          'scrollwheel' => TRUE,
          'zoomControlSize' => '2',
          'zoomControlPosition' => '1',
        ],
        'marker_settings' => [
          'displayPopupContent' => '',
          'marker' => [
            'url' => '',
          ],
          'markertitle' => t('Destination'),
          'scrollwheel' => TRUE,
          'info_window' => [
            'format' => NULL,
            'value' => '',
          ],
        ],
      ],
      'machine_name' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['coordinates'] = $values['coordinates'];
    $this->configuration['colorized_map_styles'] = $values['colorized_map_styles'];
    $this->configuration['additional_settings'] = $values['additional_settings'];

    // Save entity id in the block configuration page.
    // I cannot get block id on block build so I save entity id in such ugly way.
    // todo: need to change it somehow.
    $user_input = $form_state->getUserInput();
    if (isset($user_input['id']) && $user_input['id']) {
      $this->configuration['machine_name'] = $user_input['id'];
    }

    // Process file save marker.
//    if ($values['additional_settings']['marker_settings']['marker'][0]) {
//      // Remove previous file.
//      $fid = $values['additional_settings']['marker_settings']['marker'][0];
//      $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
//      $file->delete();
//
//      // Save new one.
//      $fid = $values['additional_settings']['marker_settings']['marker'][0];
//      $file = \Drupal::entityTypeManager()->getStorage('file')->load($fid);
//      $file->setPermanent();
//      $file->save();
//    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Check API key.
    $this->checkApiKey();

    return [
      '#machine_name' => $this->getConfiguration()['machine_name'],
      '#theme' => 'colorized_gmap_output',
      '#attached' => [
        'library' => [
          'colorized_gmap/colorized_gmap.gmap_api',
          'colorized_gmap/colorized_gmap.block',
        ],
        'drupalSettings' => $this->getFormattedJsMapSettings(),
      ],
    ];
  }

  /**
   * Helper function check if google api keys is set.
   */
  public function checkApiKey() {
    $config = \Drupal::config('colorized_gmap.settings');
    $api_key = $config->get('colorized_gmap_api_key');

    if (empty($api_key)) {
      $url = Url::fromUri('http://googlegeodevelopers.blogspot.ru/2016/06/building-for-scale-updates-to-google.html');
      $url->setOptions(['external' => TRUE, 'attributes' => ['target' => '_blank']]);
      $info_link = Link::fromTextAndUrl(t('api key'), $url)->toString();

      $url = Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key');
      $url->setOptions(['external' => TRUE, 'attributes' => ['target' => '_blank']]);
      $get_key_link = Link::fromTextAndUrl(t('this'), $url)->toString();

      $url = Url::fromRoute('colorized_gmap.admin_settings');
      $settings_link = Link::fromTextAndUrl(t('module settings page'), $url )->toString();

      $missing_api_key_message = t('Google maps are no longer working without @info. Please visit @get-key page to get API key and follow further instructions. After that, please enter your api key on @settings-page.',
        ['@info' => $info_link,
          '@get-key' => $get_key_link,
          '@settings-page' => $settings_link]
      );
      drupal_set_message($missing_api_key_message, 'warning');
    }
  }

  /**
   * Helper function returns options for map controls
   * positions (comes from GMAP api v3 reference).
   */
  public function getPositionOptions() {
    $position = [
      '1' => 'Top Left',
      '2' => 'Top Center',
      '3' => 'Top Right',
      '4' => 'Left Center',
      '5' => 'Left Top',
      '6' => 'Left Bottom',
      '7' => 'Right Top',
      '8' => 'Right Center',
      '9' => 'Right Bottom',
      '10' => 'Bottom Left',
      '11' => 'Bottom Center',
      '12' => 'Bottom Right',
    ];
    return $position;
  }

  /**
   * {@inheritdoc}
   */
  public function buildFormStylers(&$form, FormStateInterface $form_state) {
    // List of available map features.
    $feature_types = [
      'water' => 'Water',
      'landscape' => 'Landscape',
      'landscape.man_made' => 'Landscape (man made)',
      'landscape.natural' => 'Landscape (natural)',
      'landscape.natural.landcover' => 'Landscape (natural landcover)',
      'landscape.natural.terrain' => 'Landscape (natural terrain)',
      'road' => 'Road',
      'road.highway' => 'Road (highway)',
      'road.highway.controlled_access' => 'Road highway (controlled access)',
      'road.arterial' => 'Road (Arterial)',
      'road.local' => 'Road (local)',
      'poi' => 'Poi',
      'poi.park' => 'Poi (park)',
      'poi.business' => 'Poi (business)',
      'poi.attraction' => 'Poi (attraction)',
      'poi.medical' => 'Poi (medical)',
      'poi.school' => 'Poi (school)',
      'poi.sports_complex' => 'Poi (sports complex)',
      'poi.government' => 'Poi (government)',
      'poi.place_of_worship' => 'Poi (place of worship)',
      'administrative' => 'Administrative',
      'administrative.country' => 'Administrative (country)',
      'administrative.land_parcel' => 'Administrative (land parcel)',
      'administrative.locality' => 'Administrative (locality)',
      'administrative.neighborhood' => 'Administrative (neighborhood)',
      'administrative.province' => 'Administrative (province)',
      'all' => 'All',
      'transit' => 'Transit',
      'transit.line' => 'Transit (line)',
      'transit.station' => 'Transit station',
      'transit.station.airport' => 'Transit station (airport)',
      'transit.station.bus' => 'Transit station (bus)',
      'transit.station.rail' => 'Transit station (rail)',
    ];
    // List of available map elements.
    $elements = [
      'all' => 'All',
      'geometry' => 'Geometry',
      'geometry.fill' => 'Geometry fill',
      'geometry.stroke' => 'Geometry stroke',
      'labels' => 'Labels',
      'labels.icon' => 'Labels icon',
      'labels.text' => 'Labels text',
      'labels.text.fill' => 'Labels text fill',
      'labels.text.stroke' => 'Labels text stroke',
    ];

    // @todo: get styles from block config.
    $styles = $this->configuration['colorized_map_styles'];
    $styles_count = $form_state->get('styles_count');
    if (empty($styles) && !$styles_count) {
      $form_state->set('styles_count', 1);
      $styles_count = $form_state->get('styles_count');
    }
    if (!$styles_count) {
      $form_state->set('styles_count', count($styles));
      $styles_count = $form_state->get('styles_count');
    }

    $form['#tree'] = TRUE;
    $form['colorized_map_styles'] = [
      '#type' => 'table',
      '#weight' => 2,
      '#title' => t('Map styles'),
      '#prefix' => '<div id="gmap-ajax-wrapper">',
      '#suffix' => '</div>',
      '#header' => [
        t('Feature type'),
        t('Element type'),
        t('Stylers'),
      ],
    ];

    // Example map div.
    $form['markup'] = [
      '#markup' => '<div id="colorized-gmap-content"></div>',
    ];

    // @todo: move to method.
    $style_element_ajax = [
      'callback' => 'Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesMapUpdateCallback',
      'wrapper' => 'colorized-gmap-content',
      'event' => 'change',
      'progress' => [
        'type' => 'none',
      ],
    ];
    for ($i = 0; $i < $styles_count; $i++) {
      $featureType = !empty($this->configuration['colorized_map_styles'][$i]['featureType']) ? $this->configuration['colorized_map_styles'][$i]['featureType'] : '';
      $form['colorized_map_styles'][$i]['featureType'] = [
        '#type' => 'select',
        '#options' => $feature_types,
        '#default_value' => $featureType,
        '#ajax' => $style_element_ajax,
      ];
      $elementType = !empty($this->configuration['colorized_map_styles'][$i]['elementType']) ? $this->configuration['colorized_map_styles'][$i]['elementType'] : '';
      $form['colorized_map_styles'][$i]['elementType'] = [
        '#type' => 'select',
        '#options' => $elements,
        '#default_value' => $elementType,
        '#ajax' => $style_element_ajax,
      ];
      $color = !empty($this->configuration['colorized_map_styles'][$i]['stylers'][0]['color']) ? $this->configuration['colorized_map_styles'][$i]['stylers'][0]['color'] : '';
      $form['colorized_map_styles'][$i]['stylers'][0] = [
        '#tree' => TRUE,
        'color' => [
          '#title' => t('Color'),
          '#type' => 'textfield',
          '#size' => 4,
          '#default_value' => $color,
          '#attributes' => [
            'class' => ['edit_color_input'],
          ],
          '#ajax' => [
            'callback' => 'Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesMapUpdateCallback',
            'wrapper' => 'colorized-gmap-content',
            'event' => 'textfield_change',
            'progress' => [
              'type' => 'none',
            ],
          ],
        ],
      ];
      $visibility = !empty($this->configuration['colorized_map_styles'][$i]['stylers'][1]['visibility']) ? $this->configuration['colorized_map_styles'][$i]['stylers'][1]['visibility'] : '';
      $form['colorized_map_styles'][$i]['stylers'][1] = [
        'visibility' => [
          '#type' => 'select',
          '#title' => t('Visibility'),
          '#default_value' => $visibility,
          '#options' => [
            'on' => 'On',
            'off' => 'Off',
            'simplified' => 'simplified',
          ],
          '#ajax' => $style_element_ajax,
        ],
      ];
      $saturation = !empty($this->configuration['colorized_map_styles'][$i]['stylers'][2]['saturation']) ? $this->configuration['colorized_map_styles'][$i]['stylers'][2]['saturation'] : '';
      $form['colorized_map_styles'][$i]['stylers'][2] = [
        'saturation' => [
          '#type' => 'textfield',
          '#size' => 4,
          '#title' => t('Saturation'),
          '#default_value' => $saturation,
          '#ajax' => $style_element_ajax,
        ],
      ];
      $lightness = !empty($this->configuration['colorized_map_styles'][$i]['stylers'][3]['lightness']) ? $this->configuration['colorized_map_styles'][$i]['stylers'][3]['lightness'] : '';
      $form['colorized_map_styles'][$i]['stylers'][3] = [
        'lightness' => [
          '#type' => 'textfield',
          '#size' => 4,
          '#title' => t('Lightness'),
          '#default_value' => $lightness,
          '#ajax' => $style_element_ajax,
        ],
      ];
      $weight = !empty($this->configuration['colorized_map_styles'][$i]['stylers'][4]['weight']) ? $this->configuration['colorized_map_styles'][$i]['stylers'][4]['weight'] : '';
      $form['colorized_map_styles'][$i]['stylers'][4] = [
        'weight' => [
          '#type' => 'textfield',
          '#size' => 1,
          '#title' => t('Weight'),
          '#default_value' => $weight,
          '#ajax' => $style_element_ajax,
        ],
      ];
    }

    // Buttons.
    $form_state;
    $form['ajax_buttons'] = [
      '#type' => 'fieldset',
      '#weight' => 3,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['ajax_buttons']['add_more'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add More'),
      '#submit' => ['Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesAddOneMore'],
      '#ajax' => [
        'callback' => 'Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesUpdateCallback',
        'wrapper' => 'gmap-ajax-wrapper',
      ],
    ];
    $form['ajax_buttons']['remove_row'] = [
      '#type' => 'submit',
      '#value' => t('Remove Row'),
      '#submit' => ['Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesRemoveOne'],
      '#ajax' => [
        'callback' => 'Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesUpdateCallback',
        'wrapper' => 'gmap-ajax-wrapper',
      ],
    ];

    return $form;
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldset with the names in it.
   */
  public function stylesUpdateCallback(array &$form, FormStateInterface $form_state) {
    return $form['settings']['colorized_map_styles'];
  }

  /**
   * Ajax callback for updating colorized map settings.
   */
  public function stylesMapUpdateCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $settings = ColorizedGmapBlock::getFormattedJsMapAdminSettings($values['settings']);
    $response = new AjaxResponse();
    $response->addCommand(new SettingsCommand($settings, TRUE));
    return $response;
  }

  /**
   * Helper function. Builds formatted settigns array based on form state values.
   */
  public function getFormattedJsMapSettings() {
    $config = $this->getConfiguration();
    $settings = [
      'coordinates' => $config['coordinates'],
      'style' => $config['colorized_map_styles'],
      'additional_settings' => $config['additional_settings'],
      'machine_name' => $config['machine_name'],
    ];
    return ['colorized_gmap' => [$config['machine_name'] => $settings]];
  }

  /**
   * Helper function. Builds formatted settigns array based on form state values.
   */
  public function getFormattedJsMapAdminSettings($config = null) {
    if (!$config) {
      $config = $this->getConfiguration();
    }
    $settings = [
      'coordinates' => $config['coordinates'],
      'style' => $config['colorized_map_styles'],
      'additional_settings' => $config['additional_settings'],
    ];
    return ['colorized_gmap' => $settings];
  }

  /**
   * Submit handler for the "add-one-more" button.
   *
   * Increments the max counter and causes a rebuild.
   */
  public function stylesAddOneMore(array &$form, FormStateInterface $form_state) {
    $styles_count = $form_state->get('styles_count');
    $styles_count++;
    $form_state->set('styles_count', $styles_count);
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function stylesRemoveOne(array &$form, FormStateInterface $form_state) {
    $styles_count = $form_state->get('styles_count');
    if ($styles_count > 1) {
      $styles_count--;
      $form_state->set('styles_count', $styles_count);
    }
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   *
   * Decrements the max counter and causes a form rebuild.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    $name_field = $form_state->get('num_names');
    if ($name_field > 1) {
      $remove_button = $name_field - 1;
      $form_state->set('num_names', $remove_button);
    }
    $form_state->setRebuild();
  }

  /**
   * Helper function. Create form elements for map zoom position settings.
   */
  public function buildFormCoordinates(&$form, &$form_state) {

    $form['coordinates'] = [
      '#type' => 'fieldset',
      '#title' => t('Coordinates'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#weight' => -1,
    ];
    $form['coordinates']['latitude'] = [
      '#type' => 'textfield',
      '#title' => t('Latitude'),
      '#size' => 10,
      '#weight' => 0,
      '#default_value' => $this->configuration['coordinates']['latitude'],
      '#ajax' => [
        'callback' => 'Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesMapUpdateCallback',
        'wrapper' => 'colorized-gmap-content',
        'event' => 'change',
      ],
    ];
    $form['coordinates']['longitude'] = [
      '#type' => 'textfield',
      '#title' => t('Longitude'),
      '#size' => 10,
      '#weight' => 2,
      '#default_value' => $this->configuration['coordinates']['longitude'],
      '#ajax' => [
        'callback' => 'Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesMapUpdateCallback',
        'event' => 'change',
        'wrapper' => 'colorized-gmap-content',
      ],
    ];
  }


  /**
   * Helper function. Create form elements for map zoom settings.
   */
  public function buildFormZoom(&$form, &$form_state) {
    //@todo: get existing configs.

    $form['additional_settings']['zoom_controls'] = [
      '#type' => 'fieldset',
      '#title' => t('Zoom control settings'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['additional_settings']['zoom_controls']['zoom'] = [
      '#type' => 'textfield',
      '#title' => t('Zoom'),
      '#size' => 10,
      '#default_value' => $this->configuration['additional_settings']['zoom_controls']['zoom'],
      '#description' => t('Enter zoom amount'),
    ];
    $form['additional_settings']['zoom_controls']['zoomControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable zoom control'),
      '#default_value' => $this->configuration['additional_settings']['zoom_controls']['zoomControl'],
    ];
    $form['additional_settings']['zoom_controls']['scrollwheel'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable scrollwheel zoom'),
      '#default_value' => $this->configuration['additional_settings']['zoom_controls']['scrollwheel'],
    ];
    $form['additional_settings']['zoom_controls']['zoomControlSize'] = [
      '#type' => 'select',
      '#title' => t('Zoom Control Size'),
      '#options' => [
        '1' => 'Small',
        '2' => 'Large',
      ],
      '#default_value' => $this->configuration['additional_settings']['zoom_controls']['zoomControlSize'],
    ];
    $form['additional_settings']['zoom_controls']['zoomControlPosition'] = [
      '#type' => 'select',
      '#title' => t('Zoom Control Position'),
      '#options' => $this->getPositionOptions(),
      '#default_value' => $this->configuration['additional_settings']['zoom_controls']['zoomControlPosition'],
    ];
  }


  /**
   * Helper function. Create form elements for map controls settings.
   */
  function buildFormControls(&$form, &$form_state, $entity = NULL) {
    //@todo: get existing configs.

    $form['additional_settings']['controls'] = [
      '#type' => 'fieldset',
      '#title' => t('Controls'),
      '#weight' => 1,
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['additional_settings']['controls']['min_drag_width'] = [
      '#type' => 'textfield',
      '#title' => t('Min draggable screnn width'),
      '#size' => 5,
      '#description' => t('If your screen width is greater, the map will be draggable. Enter 0 to make map always draggable.'),
      '#default_value' => $this->configuration['additional_settings']['controls']['min_drag_width'],
      '#field_suffix' => 'px',
    ];
    $form['additional_settings']['controls']['streetViewControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable streetview control'),
      '#default_value' => $this->configuration['additional_settings']['controls']['min_drag_width'],
    ];
    $form['additional_settings']['controls']['panControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable pan control'),
      '#default_value' => $this->configuration['additional_settings']['controls']['panControl'],
    ];
    $form['additional_settings']['controls']['mapTypeControl'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable map type control'),
      '#default_value' => $this->configuration['additional_settings']['controls']['mapTypeControl'],
    ];
  }

  /**
   * Helper function. Create form elements for map controls position settings.
   */
  public function buildFormControlsPosition(&$form, &$form_state) {
    //@todo: get existing configs.

    $form['additional_settings']['controls_position'] = [
      '#type' => 'fieldset',
      '#title' => t('Controls Position'),
      '#weight' => 2,
      '#attributes' => [
        'class' => ['controls_position'],
      ],
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['additional_settings']['controls_position']['streetViewControl'] = [
      '#type' => 'select',
      '#title' => t('Streetview control position'),
      '#options' => $this->getPositionOptions(),
      '#default_value' => $this->configuration['additional_settings']['controls_position']['streetViewControl'],
    ];
    $form['additional_settings']['controls_position']['panControl'] = [
      '#type' => 'select',
      '#title' => t('Pan control position'),
      '#options' => $this->getPositionOptions(),
      '#default_value' => $this->configuration['additional_settings']['controls_position']['panControl'],
    ];
    $form['additional_settings']['controls_position']['mapTypeControl'] = [
      '#type' => 'select',
      '#title' => t('Map type control position'),
      '#options' => $this->getPositionOptions(),
      '#default_value' => $this->configuration['additional_settings']['controls_position']['mapTypeControl'],
    ];
  }


  /**
   * Helper function. Create form elements for map marker settings.
   */
  public function  buildFormMarker(&$form, &$form_state) {
    $form['additional_settings']['marker_settings'] = [
      '#type' => 'fieldset',
      '#title' => t('Marker'),
      '#attributes' => [
        'class' => ['gmap_colorizer_input'],
      ],
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['additional_settings']['marker_settings']['markertitle'] = [
      '#type' => 'textfield',
      '#size' => 30,
      '#title' => t('Title'),
      '#default_value' => $this->configuration['additional_settings']['marker_settings']['markertitle'],
      '#description' => t('Title to display on the mouseover'),
    ];
    $form['additional_settings']['marker_settings']['displayPopupContent'] = [
      '#type' => 'checkbox',
      '#title' => t('Open a marker\'s content when the page is loaded'),
      '#default_value' => $this->configuration['additional_settings']['marker_settings']['displayPopupContent'],
    ];
    $form['additional_settings']['marker_settings']['info_window'] = [
      '#type' => 'text_format',
      '#title' => t('Marker Popup Content (info window)'),
      '#description' => t('Text for info window. An InfoWindow displays content (usually text or images) in a popup window above the map after clicking on the marker'),
      '#format' => $this->configuration['additional_settings']['marker_settings']['info_window']['format'],
      '#default_value' => $this->configuration['additional_settings']['marker_settings']['info_window']['value'],
      '#ajax' => [
        'callback' => 'Drupal\colorized_gmap\Plugin\Block\ColorizedGmapBlock::stylesMapUpdateCallback',
        'event' => 'change',
        'wrapper' => 'colorized-gmap-content',
      ],
    ];
    $form['additional_settings']['marker_settings']['marker'] = [
      'url' => [
        '#type' => 'textfield',
        '#size' => 30,
        '#title' => t('Url icon'),
        '#default_value' => $this->configuration['additional_settings']['marker_settings']['marker']['url'],
      ]
    ];
  }

}
