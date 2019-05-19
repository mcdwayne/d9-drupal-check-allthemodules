<?php

namespace Drupal\ymaps_geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Plugin implementation of the 'ymaps geolocation' formatter.
 *
 * @FieldFormatter(
 *   id = "ymaps_geolocation",
 *   label = @Translation("Geolocation Yandex map"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class YmapsGeolocationFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {

      $map_id = $items->getName() . '-' . $delta;

      $lng = $item->lng;
      $lat = $item->lat;

      $element = [
        '#map_id' => $map_id,
        '#theme' => 'ymaps_geolocation_formatter',
        '#width' => $this->getSetting('width'),
        '#height' => $this->getSetting('height'),
        '#lng' => $lng,
        '#lat' => $lat,
      ];

      $settings = $this->getSettings();

      $map_center = $settings['center'];
      $center_arr = ($map_center) ? explode(',', $map_center) : [0, 0];

      // Balloon node token replacement.
      $replacements = [];
      $entity = $item->getEntity();
      if ($entity->getEntityTypeId() == 'node') {
        $replacements['node'] = Node::load($entity->id());
      }
      $balloonContent = $settings['placemark']['balloonContent'];
      $balloonContent = \Drupal::token()->replace($balloonContent, $replacements);

      $map = [
        'init' => [
          'center' => $center_arr,
          'zoom' => $settings['zoom'] ?? NULL,
          'type' => $settings['type'] ?? NULL,
          'behaviors' => json_decode($settings['behaviors']) ?? NULL,
          'controls' => json_decode($settings['controls']) ?? NULL,
        ],
        'display' => [
          'width' => $settings['width'],
          'height' => $settings['height'],
          'auto_centering' => $settings['auto_centering'],
          'auto_zooming' => $settings['auto_zooming'],
        ],

        'placemark' => [
          'coordinates' => [$lat, $lng],
          'preset' => $settings['placemark']['preset'],
          'balloonContent' => $balloonContent ?? NULL,
        ],
        'edit' => FALSE,
      ];

      $elements[$delta] = $element;
      $elements['#attached']['drupalSettings']['ymaps'][$map_id] = $map;
    }

    $elements['#attached']['library'][] = 'ymaps_geolocation/ymaps-init';

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {

    $settings = $this->getSettings();

    $element['width'] = [
      '#title' => $this->t('Map width'),
      '#type' => 'textfield',
      '#default_value' => $settings['width'],
    ];

    $element['height'] = [
      '#title' => $this->t('Map height'),
      '#type' => 'textfield',
      '#default_value' => $settings['height'],
    ];

    $element['type'] = [
      '#title' => $this->t('Map type'),
      '#type' => 'select',
      '#options' => _ymaps_geolocation_get_map_types(),
      '#default_value' => $settings['type'],
    ];

    $element['center'] = [
      '#title' => $this->t('Map center'),
      '#description' => t('Map center coordinates: Longtitude,Latitude'),
      '#type' => 'textfield',
      '#default_value' => $settings['center'],
    ];

    $element['zoom'] = [
      '#title' => $this->t('Map zoom level'),
      '#description' => t('From 1 to 16'),
      '#type' => 'textfield',
      '#default_value' => $settings['zoom'],
    ];

    $element['behaviors'] = [
      '#title' => $this->t('Map behaviors'),
      '#type' => 'textfield',
      '#default_value' => $settings['behaviors'],
    ];

    $element['controls'] = [
      '#title' => $this->t('Map controls'),
      '#type' => 'textfield',
      '#default_value' => $settings['controls'],
    ];

    $element['auto_centering'] = [
      '#type' => 'checkbox',
      '#title' => t('Map auto centering'),
      '#default_value' => $settings['auto_centering'],
    ];

    $element['auto_zooming'] = [
      '#type' => 'checkbox',
      '#title' => t('Map auto zooming'),
      '#default_value' => $settings['auto_zooming'],
    ];

    $element['placemark'] = [
      '#type' => 'fieldset',
      '#title' => t('Placemark options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];

    $element['placemark']['preset'] = [
      '#title' => $this->t('Preset'),
      '#type' => 'textfield',
      '#description' => '<a href="https://tech.yandex.ru/maps/doc/jsapi/2.1/ref/reference/option.presetStorage-docpage/">' . t('See preset options page.') . '</a>',
      '#default_value' => $settings['placemark']['preset'],
    ];

    $element['placemark']['balloonContent'] = [
      '#title' => $this->t('Balloon content'),
      '#type' => 'textfield',
      '#default_value' => $settings['placemark']['balloonContent'],
    ];

    $element['placemark']['token_tree'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['node'],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'width' => '100%',
      'height' => '400px',
      'center' => '38.975996,45.040216',
      'zoom' => '10',
      'type' => 'yandex#map',
      'behaviors' => '["scrollZoom","dblClickZoom","drag"]',
      'controls' => '["zoomControl", "searchControl", "typeSelector",  "fullscreenControl", "routeButtonControl"]',
      'auto_centering' => TRUE,
      'auto_zooming' => TRUE,
      'placemark' => [
        'preset' => 'islands#redDotIcon',
        'balloonContent' => '',
      ],
    ] + parent::defaultSettings();
  }

}
