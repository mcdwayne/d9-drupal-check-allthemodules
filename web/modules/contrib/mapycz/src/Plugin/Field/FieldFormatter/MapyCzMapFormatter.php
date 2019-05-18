<?php

namespace Drupal\mapycz\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\mapycz\MapyCzCore;

/**
 * Plugin implementation of the 'mapycz_map' formatter.
 *
 * @FieldFormatter(
 *   id = "mapycz_map",
 *   module = "mapycz",
 *   label = @Translation("Mapy CZ - Map"),
 *   field_types = {
 *     "mapycz"
 *   }
 * )
 */
class MapyCzMapFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = [];

    $settings['width'] = '100%';
    $settings['height'] = '350px';
    $settings['type'] = 'basic';

    $settings += parent::defaultSettings();

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();

    $element['width'] = [
      '#title' => 'Šířka',
      '#type' => 'textfield',
      '#default_value' => $settings['width'],
      '#description' => 'Vložte velikost a jednotky, např 200px nebo 100%.',
    ];

    $element['height'] = [
      '#title' => 'Výška',
      '#type' => 'textfield',
      '#default_value' => $settings['height'],
      '#description' => 'Vložte velikost a jednotky, např 200px nebo 100%.',
    ];

    $element['type'] = [
      '#title' => 'Typ mapy',
      '#type' => 'select',
      '#options' => MapyCzCore::getMapTypeOptions(),
      '#default_value' => $settings['type'],
      '#description' => $this->t("Choose default map type to show. If map in a node has it's own type set, it will be used."),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();

    $summary = [];
    $summary[] = $this->t('Map type: @type', ['@type' => $settings['type']]);
    $summary[] = $this->t('Width: @width', ['@width' => $settings['width']]);
    $summary[] = $this->t('Height: @height', ['@height' => $settings['height']]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();

    $element = [
      '#attached' => [
        'library' => [
          'mapycz/mapycz.frontend',
        ],
      ],
    ];

    foreach ($items as $delta => $item) {
      $map_id = uniqid('mapycz-widget-' . $delta);
      $element[$delta] = [
        '#theme' => 'mapycz_map',
        '#map_id' => $map_id,
        '#center' => [
          'lat' => $item->data['center_lat'],
          'lng' => $item->data['center_lng'],
        ],
        '#zoom' => $item->data['zoom'],
        '#type' => $item->data['type'] == 'default' ? $settings['type'] : $item->data['type'],
        '#markers' => [
          0 => [
            'lat' => $item->lat,
            'lng' => $item->lng,
          ],
        ],
        '#width' => $settings['width'],
        '#height' => $settings['height'],
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => [
              'id' => $map_id . '-wrapper',
              'class' => 'mapycz-wrapper',
            ],
          ],
        ],
      ];
    }

    return $element;
  }

}
