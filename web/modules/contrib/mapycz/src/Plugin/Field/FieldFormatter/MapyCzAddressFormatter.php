<?php

namespace Drupal\mapycz\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'mapycz_address' formatter.
 *
 * @FieldFormatter(
 *   id = "mapycz_address",
 *   module = "mapycz",
 *   label = @Translation("Mapy CZ - Address"),
 *   field_types = {
 *     "mapycz"
 *   }
 * )
 */
class MapyCzAddressFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $settings = $this->getSettings();
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $settings = $this->getSettings();
    if (!isset($items[0])) {
      return [];
    }

    $element = [
      '#attached' => [
        'library' => [
          'mapycz/mapycz.frontend',
        ],
      ],
    ];

    $map_id = uniqid('mapycz-widget-' . 0);
    $element[0] = [
      '#theme' => 'mapycz_address',
      '#map_id' => $map_id,
      '#marker' => [
        'lat' => $items[0]->lat,
        'lng' => $items[0]->lng,
      ],
      '#theme_wrappers' => [
        'container' => [
          '#attributes' => [
            'id' => $map_id . '-wrapper',
            'class' => 'mapycz-wrapper',
          ],
        ],
      ],
    ];
    return $element;
  }

}
