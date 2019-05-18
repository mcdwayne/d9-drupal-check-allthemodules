<?php

namespace Drupal\google_place_autocomplete\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\google_place_autocomplete\Services\GeocoderConsumerService;
use Drupal\Core\State\StateInterface;

/**
 * Plugin implementation of the 'google_place_map' formatter.
 *
 * @FieldFormatter(
 *   id = "google_place_map",
 *   module = "google_place_autocomplete",
 *   label = @Translation("Google Map"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class GoogleMapFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * Geocode Consumer Object.
   *
   * @var \Drupal\google_place_autocomplete\Services\GeocoderConsumerService
   */
  protected $geoCodes;
  /**
   * The state keyvalue collection.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs Field object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The label settings.
   * @param string $view_mode
   *   The view mode settings.
   * @param array $third_party_settings
   *   Third party settings.
   * @param \Drupal\google_place_autocomplete\Services\GeocoderConsumerService $geoCodes
   *   Geocoder Consumer Service.
   * @param \Drupal\Core\State\StateInterface $state
   *   State Key/Value Object.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, GeocoderConsumerService $geoCodes, StateInterface $state) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->geoCodes = $geoCodes;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('google_place_autocomplete.geocodes'),
      $container->get('state')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Google map render options.');

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'zoom_level' => 14,
      'map_type' => 'roadmap',
      'map_width' => '100%',
      'map_height' => '500px',
      'controls' => 1,
      'drag' => 1,
      'infowindow' => 1,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['zoom_level'] = [
      '#type' => 'select',
      '#title' => $this->t('Zoom Level'),
      '#options' => [
        8 => '8',
        9 => '9',
        10 => '10',
        11 => '11',
        12 => '12',
        13 => '13',
        14 => '14 (Default)',
        15 => '15',
        16 => '16',
        17 => '17',
        18 => '18',
        19 => '19',
        20 => '20',
        21 => '21',
      ],
      '#default_value' => $this->getSetting('zoom_level'),
    ];

    $element['map_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of Map'),
      '#options' => [
        'roadmap' => $this->t('Map'),
        'satellite' => $this->t('Satellite'),
        'hybrid' => $this->t('Hybrid'),
        'terrain' => $this->t('Terrain'),
      ],
      '#default_value' => $this->getSetting('map_type'),
    ];

    $element['map_width'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Width'),
      '#default_value' => $this->getSetting('map_width'),
    ];

    $element['map_height'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Map Height'),
      '#default_value' => $this->getSetting('map_height'),
    ];

    $element['controls'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable control options'),
      '#default_value' => $this->getSetting('controls'),
    ];

    $element['drag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Disable Map Draggable'),
      '#default_value' => $this->getSetting('drag'),
    ];

    $element['infowindow'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show InfoWindow'),
      '#default_value' => $this->getSetting('infowindow'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $apiKey = $this->state->get('place_api_key');
    foreach ($items as $delta => $item) {
      $geocodes = $this->geoCodes->geoLatLong($item->value);
      $map_vars = [
        'zoom_level' => $this->getSetting('zoom_level'),
        'map_type' => $this->getSetting('map_type'),
        'map_width' => $this->getSetting('map_width'),
        'map_height' => $this->getSetting('map_height'),
        'controls' => $this->getSetting('controls'),
        'drag' => $this->getSetting('drag'),
        'infowindow' => $this->getSetting('infowindow'),
        'content' => $item->value,
        'latitude' => $geocodes['latitude'],
        'longitude' => $geocodes['longitude'],
      ];
      $elements[$delta] = [
        '#type' => 'container',
        '#prefix' => '<div id="map">',
        '#suffix' => '</div>',
      ];
    }
    $googleMapKey = [
      '#tag' => 'script',
      '#attributes' => ['src' => '//maps.googleapis.com/maps/api/js?key=' . $apiKey . '&sensor=true&libraries=places'],
    ];
    $elements['#attached']['html_head'][] = [$googleMapKey, 'googleMapKey'];

    $elements['#attached']['drupalSettings']['map_view']['autocomplete'] = $map_vars;
    $elements['#attached']['library'][] = 'google_place_autocomplete/google_place_autocomplete.location';
    return $elements;
  }

}
