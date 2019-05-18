<?php

namespace Drupal\baidu_map_geofield;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GeofieldBaiduMapFieldTrait.
 *
 * Provide common functions for Geofield Map fields.
 *
 * @package Drupal\baidu_map_geofield
 */
trait GeofieldBaiduMapFieldTrait {

  /**
   * Baidu Map Types Options.
   *
   * @var array
   */
  protected $bMapTypesOptions = [
    'normal' => 'Normal',
    'perspective' => 'Perspective',
    'satellite' => 'Satellite',
    'hybrid' => 'Hybrid',
  ];

  /**
   * Google Map Types Options.
   *
   * @var array
   */
  protected $infowindowFieldTypesOptions = [
    'string_long',
    'string',
    'text',
    'text_long',
    "text_with_summary",
  ];

  protected $customMapStylePlaceholder = '[{"elementType":"geometry","stylers":[{"color":"#1d2c4d"}]},{"elementType":"labels.text.fill","stylers":[{"color":"#8ec3b9"}]},{"elementType":"labels.text.stroke","stylers":[{"color":"#1a3646"}]},{"featureType":"administrative.country","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"administrative.province","elementType":"geometry.stroke","stylers":[{"color":"#4b6878"}]},{"featureType":"water","elementType":"geometry","stylers":[{"color":"#0e1626"}]},{"featureType":"water","elementType":"labels.text.fill","stylers":[{"color":"#4e6d70"}]}]';

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface $this->link
   */

  /**
   * Get the GMap Api Key from the geofield_map settings/configuration.
   *
   * @return string
   *   The GMap Api Key
   */
  private function getBaiduMapApiKey() {
    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config */
    $config = $this->config;
    $baidu_map_settings = $config->getEditable('baidu_map.settings');
    $api_key = $baidu_map_settings->get('baidu_map_api_key');

    return $api_key;
  }

  /**
   * Get the Default Settings.
   *
   * @return array
   *   The default settings.
   */
  public static function getDefaultSettings() {
    return [
      'map_type' => 'normal',
      'map_dimensions' => [
        'width' => '100%',
        'height' => '450px',
      ],
      'map_style' => [
        'map_style' => 'normal'
      ],
      'baidu_map_geoaddress_field' => [
        'field' => '0',
        'hidden' => FALSE,
        'disabled' => TRUE,
      ],
    ];
  }

  /**
   * Generate the Google Map Settings Form.
   *
   * @param array $form
   *   The form where the settings form is being included in.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $settings
   *   Form settings.
   * @param array $default_settings
   *   Default settings.
   *
   * @return array
   *   The GMap Settings Form*/
  public function generateBmapSettingsForm(array $form, FormStateInterface $form_state, array $settings, array $default_settings) {

    $elements = [];

    // Attach Geofield Map Library.
    $elements['#attached']['library'] = [
      'geofield_map/geofield_map_general',
    ];

    // Set Google Api Key Element.
    $this->setMapBaiduMapApiKeyElement($elements);

    // Set Map Dimension Element.
    $this->setMapDimensionsElement($settings, $elements);

    // Set Map Zoom and Pan Element.
    $this->setMapStyleElement($settings, $default_settings, $elements);
    return $elements;

  }

  /**
   * Form element validation handler for a Map Zoom level.
   *
   * {@inheritdoc}
   */
  public static function zoomLevelValidate($element, FormStateInterface &$form_state) {
    // Get to the actual values in a form tree.
    $parents = $element['#parents'];
    $values = $form_state->getValues();
    for ($i = 0; $i < count($parents) - 1; $i++) {
      $values = $values[$parents[$i]];
    }
    // Check the initial map zoom level.
    $zoom = $element['#value'];
    $min_zoom = $values['min'];
    $max_zoom = $values['max'];
    if ($zoom < $min_zoom || $zoom > $max_zoom) {
      $form_state->setError($element, t('The @zoom_field should be between the Minimum and the Maximum Zoom levels.', ['@zoom_field' => $element['#title']]));
    }
  }

  /**
   * Form element validation handler for the Map Max Zoom level.
   *
   * {@inheritdoc}
   */
  public static function maxZoomLevelValidate($element, FormStateInterface &$form_state) {
    // Get to the actual values in a form tree.
    $parents = $element['#parents'];
    $values = $form_state->getValues();
    for ($i = 0; $i < count($parents) - 1; $i++) {
      $values = $values[$parents[$i]];
    }
    // Check the max zoom level.
    $min_zoom = $values['min'];
    $max_zoom = $element['#value'];
    if ($max_zoom && $max_zoom <= $min_zoom) {
      $form_state->setError($element, t('The Max Zoom level should be above the Minimum Zoom level.'));
    }
  }

  /**
   * Form element validation handler for a Custom Map Style Name Required.
   *
   * {@inheritdoc}
   */
  public static function customMapStyleValidate($element, FormStateInterface &$form_state) {
    // Get to the actual values in a form tree.
    $parents = $element['#parents'];
    $values = $form_state->getValues();
    for ($i = 0; $i < count($parents) - 1; $i++) {
      $values = $values[$parents[$i]];
    }
    if ($values['custom_style_control'] && empty($element['#value'])) {
      $form_state->setError($element, t('The @field cannot be empty.', ['@field' => $element['#title']]));
    }
  }

  /**
   * Form element json format validation handler.
   *
   * {@inheritdoc}
   */
  public static function jsonValidate($element, FormStateInterface &$form_state) {
    $element_values_array = JSON::decode($element['#value']);
    // Check the jsonValue.
    if (!empty($element['#value']) && $element_values_array == NULL) {
      $form_state->setError($element, t('The @field field is not valid Json Format.', ['@field' => $element['#title']]));
    }
    elseif (!empty($element['#value'])) {
      $form_state->setValueForElement($element, JSON::encode($element_values_array));
    }
  }

  /**
   * Form element url format validation handler.
   *
   * {@inheritdoc}
   */
  public static function urlValidate($element, FormStateInterface &$form_state) {
    $path = $element['#value'];
    // Check the jsonValue.
    if (UrlHelper::isExternal($path) && !UrlHelper::isValid($path, TRUE)) {
      $form_state->setError($element, t('The @field field is not valid Url Format.', ['@field' => $element['#title']]));
    }
    elseif (!UrlHelper::isExternal($path)) {
      $path = Url::fromUri('base:' . $path, ['absolute' => TRUE])->toString();
      if (!UrlHelper::isValid($path)) {
        $form_state->setError($element, t('The @field field is not valid internal Drupal path.', ['@field' => $element['#title']]));
      }
    }
  }

  /**
   * Pre Process the MapSettings.
   *
   * Performs some preprocess on the maps settings before sending to js.
   *
   * @param array $map_settings
   *   The map settings.
   */
  protected function preProcessMapSettings(array &$map_settings) {
    // Set the gmap_api_key as map settings.
    $map_settings['bmap_api_key'] = $this->getBaiduMapApiKey();

    // Transform into simple array values the map_type_control_options_type_ids.
    $map_settings['map_controls']['map_type_control_options_type_ids'] = array_keys(array_filter($map_settings['map_controls']['map_type_control_options_type_ids'], function ($value) {
      return $value !== 0;
    }));

    // Generate Absolute icon_image_path, if it is not.
    $icon_image_path = $map_settings['map_marker_and_infowindow']['icon_image_path'];
    if (!empty($icon_image_path) && !UrlHelper::isExternal($map_settings['map_marker_and_infowindow']['icon_image_path'])) {
      $map_settings['map_marker_and_infowindow']['icon_image_path'] = Url::fromUri('base:' . $icon_image_path, ['absolute' => TRUE])
        ->toString();
    }
  }

  /**
   * Transform Geofield data into Geojson features.
   *
   * @param mixed $items
   *   The Geofield Data Values.
   * @param string $description
   *   The description value.
   * @param mixed $additional_data
   *   Additional data to be added to the feature properties, i.e.
   *   GeofieldGoogleMapViewStyle will add row fields (already rendered).
   *
   * @return array
   *   The data array for the current feature, including Geojson and additional
   *   data.
   */
  protected function getGeoJsonData($items, $description = NULL, $additional_data = NULL) {
    $data = [];
    foreach ($items as $delta => $item) {

      /* @var \Point $geometry */
      if (is_a($item, '\Drupal\geofield\Plugin\Field\FieldType\GeofieldItem') && isset($item->value)) {
        $geometry = $this->geoPhpWrapper->load($item->value);
      }
      elseif (preg_match('/^(POINT).*\(.*.*\)$/', $item)) {
        $geometry = $this->geoPhpWrapper->load($item);
      }
      if (isset($geometry)) {
        $datum = [
          "type" => "Feature",
          "geometry" => json_decode($geometry->out('json')),
        ];
        $datum['properties'] = [
          // If a multivalue field value with the same index exist, use this,
          // else use the first item as fallback.
          'description' => isset($description[$delta]) ? $description[$delta] : (isset($description[0]) ? $description[0] : NULL),
          'data' => $additional_data,
        ];
        $data[] = $datum;
      }
    }
    return $data;
  }

  /**
   * Set Map Google Api Key Element.
   *
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapBaiduMapApiKeyElement(array &$elements) {

    $bmap_api_key = $this->getBaiduMapApiKey();

    // Define the Google Maps API Key value message markup.
    if (!empty($bmap_api_key)) {
      $baidu_map_api_key_value = $this->t('<strong>Baidu Map Api Key:</strong> @bmap_api_key_link', [
        '@bmap_api_key_link' => $this->link->generate($bmap_api_key, Url::fromRoute('baidu_map.settings', [], [
          'query' => [
            'destination' => Url::fromRoute('<current>')
              ->toString(),
          ],
        ])),
      ]);
    }
    else {
      $baidu_map_api_key_value = t("<span class='geofield-baidumap-map-warning'>Baidu Map Api Key missing<br>The Widget Geocode and ReverseGeocode functionalities won't be available.</span> @settings_page_link", [
        '@settings_page_link' => $this->link->generate(t('Set it in the Geofield Map Configuration Page'), Url::fromRoute('baidu_map.settings', [], [
          'query' => [
            'destination' => Url::fromRoute('<current>')
              ->toString(),
          ],
        ])),
      ]);
    }

    $elements['baidu_map_api_key'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $baidu_map_api_key_value,
    ];
  }

  /**
   * Set Map Dimension Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapDimensionsElement(array $settings, array &$elements) {
    $elements['map_dimensions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Dimensions'),
    ];
    $elements['map_dimensions']['width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map width'),
      '#default_value' => $settings['map_dimensions']['width'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default width of a Baidu map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>.'),
      '#required' => TRUE,
    ];
    $elements['map_dimensions']['height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map height'),
      '#default_value' => $settings['map_dimensions']['height'],
      '#size' => 25,
      '#maxlength' => 25,
      '#description' => $this->t('The default height of a Baidu map, as a CSS length or percentage. Examples: <em>50px</em>, <em>5em</em>, <em>2.5in</em>, <em>95%</em>'),
      '#required' => TRUE,
    ];
  }

  /**
   * Set Map Zoom and Pan Element.
   *
   * @param array $settings
   *   The Form Settings.
   * @param array $default_settings
   *   The default_settings.
   * @param array $elements
   *   The Form element to alter.
   */
  private function setMapStyleElement(array $settings, array $default_settings, array &$elements) {
    $elements['map_style'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Map Style'),
    ];

    $style_settings = $settings['map_style'];
    // Define the Map Style.
    // 原色（normal）、深色（dark）、浅色（light）.
    $elements['map_style']['baidu_map_geofield_style'] = array(
      '#type' => 'select',
      '#title' => $this->t('Default Map Style'),
      '#options' => array(
        'normal' => $this->t('Normal'),
        'dark' => $this->t('Dark'),
        'light' => $this->t('Light'),
      ),
      '#default_value' => $style_settings['baidu_map_geofield_style'],
      '#description' => $this->t('Select the default map display style.'),
    );
    // Show traffic option as a checkbox.
    $elements['map_style']['baidu_map_geofield_showtraffic'] = array(
      '#type' => 'checkbox',
      '#title' => t('Show traffic'),
      '#default_value' => $style_settings['baidu_map_geofield_showtraffic'],
      '#description' => t('Display or hide traffic information on the map.'),
    );
    // Define the navigation controls select list with several options.
    $elements['map_style']['baidu_map_geofield_navigationcontrol'] = array(
      '#type' => 'select',
      '#title' => $this->t('Navigation controls'),
      '#options' => array(
        '' => $this->t('Hidden'),
        'large' => $this->t('Large'),
        'pan' => $this->t('Pan'),
        'small' => $this->t('Small'),
        'zoom' => $this->t('Zoom'),
      ),
      '#default_value' => $style_settings['baidu_map_geofield_navigationcontrol'],
      '#description' => $this->t('Display or hide map navigation controls in the top left corner, including the cursor and the zoom level bar.'),
    );
    // Enable scrollwheel zooming as a checkbox.
    $elements['map_style']['baidu_map_geofield_scrollwheel'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Scrollwheel'),
      '#description' => $this->t('Enable scrollwheel zooming'),
      '#default_value' => $style_settings['baidu_map_geofield_scrollwheel'],
    );
    // Enable dragging on the map as a checkbox.
    $elements['map_style']['baidu_map_geofield_draggable'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Draggable'),
      '#description' => $this->t('Enable dragging on the map'),
      '#default_value' => $style_settings['baidu_map_geofield_draggable'],
    );
    // Show map type control option as a checkbox.
    $elements['map_style']['baidu_map_geofield_maptypecontrol'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show map type control'),
      '#default_value' => $style_settings['baidu_map_geofield_maptypecontrol'],
      '#description' => $this->t('The map type control is displayed in the top right corner and allows users to switch between map display types.'),
    );
    // Show the map scale control option as a checkbox.
    $elements['map_style']['baidu_map_geofield_scalecontrol'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Show the map scale'),
      '#default_value' => $style_settings['baidu_map_geofield_scalecontrol'],
      '#description' => $this->t('Display or hide the map scale in the bottom left corner.'),
    );

    $elements['map_style']['baidu_map_geofield_zoom'] = array(
      '#type' => 'select',
      '#title' => $this->t('Zoom'),
      '#default_value' => $style_settings['baidu_map_geofield_zoom'],
      // drupal_map_assoc(range(1, 18))
      '#options' => array_merge(array('auto' => 'Automatic'), array_combine(range(1, 18), range(1, 18))),
      '#description' => $this->t('The default zoom level of a Baidu map, ranging from 0 to 20 (the greatest). Select <em>Automatic</em> for the map to automatically center and zoom to show all locations.'),
    );
  }
}
