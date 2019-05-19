<?php

namespace Drupal\yamaps\Plugin\Field\FieldWidget;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'yamaps_default' widget.
 *
 * @FieldWidget(
 *   id = "yamaps_default",
 *   label = @Translation("Yandex Map Field default"),
 *   field_types = {
 *     "yamaps"
 *   }
 * )
 */
class YamapsDefaultWidget extends WidgetBase {

  protected $map;

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $this->map = [];
    $map_id = $items->getName() . '-' . $delta;
    $element += [
      '#type' => 'fieldset',
      '#title' => $this->t('Map'),
    ];

    $element['#attached']['library'][] = 'yamaps/yandex-map-api';

    $this->initMap($element, $map_id, $items[$delta]->coords);

    $this->addPlacemarksToMap($element, $map_id, $items[$delta]->placemarks);

    $this->addLinesToMap($element, $map_id, $items[$delta]->lines);

    $this->addPolygonsToMap($element, $map_id, $items[$delta]->polygons);


    $element['#attached']['library'][] = 'yamaps/yamaps-map';
    $element['#attached']['drupalSettings']['yamaps'] = [$map_id => $this->map];

    return $element;
  }

  /**
   * Add placemarks to map and enable tool.
   *
   * @param array $elements
   * @param $map_id
   * @param $placemarks
   */
  private function addPlacemarksToMap(array &$element, $map_id, $placemarks) {
    $element['placemarks'] = [
      '#type' => 'hidden',
      '#default_value' => $placemarks,
      '#attributes' => ['class' => ['field-yamaps-placemarks-' . $map_id]],
    ];

    if ($this->getFieldSetting('enable_lines')) {
      $element['#attached']['library'][] = 'yamaps/yamaps-placemark';
      $this->map['placemarks'] = Json::decode($placemarks);

    }
  }

  /**
   * Add lines to map and enable tool.
   *
   * @param array $elements
   * @param $map_id
   * @param $placemarks
   */
  private function addLinesToMap(array &$element, $map_id, $lines) {
    $element['lines'] = [
      '#type' => 'hidden',
      '#default_value' => $lines,
      '#attributes' => ['class' => ['field-yamaps-lines-' . $map_id]],
    ];

    if ($this->getFieldSetting('enable_lines')) {
      $element['#attached']['library'][] = 'yamaps/yamaps-line';

      $this->map['lines'] = Json::decode($lines);
    }
  }

  /**
   * Add polygons to map and enable tool.
   *
   * @param array $elements
   * @param $map_id
   * @param $placemarks
   */
  private function addPolygonsToMap(array &$element, $map_id, $polygons) {
    $element['polygons'] = [
      '#type' => 'hidden',
      '#default_value' => $polygons,
      '#attributes' => ['class' => ['field-yamaps-polygons-' . $map_id]],
    ];

    if ($this->getFieldSetting('enable_polygons')) {
      $element['#attached']['library'][] = 'yamaps/yamaps-polygon';

      $this->map['polygons'] = Json::decode($polygons);
    }
  }

  /**
   * Create map container and .
   *
   * @param array $elements
   * @param $map_id
   * @param $placemarks
   */
  private function initMap(&$element, $map_id, $coords) {

    $element['map_container'] = [
      '#title' => $this->t('Preview'),
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#attributes' => [
        'id' => $map_id,
        'class' => 'yamaps-field-map',
        'style' => 'width: 100%; height: 400px;',
      ],
    ];

    $element['coords'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Coordinates'),
      '#default_value' => $coords,
      '#attributes' => [
        'class' => ['field-yamaps-coords-' . $map_id],
      ],
    ];

    $element['type'] = [
      '#type' => 'hidden',
      '#default_value' => 'yandex#map',
    ];

    $coords = Json::decode($coords);

    $this->map['init'] = [
      'center' => $coords['center'] ?? NULL,
      'zoom' => $coords['zoom'] ?? NULL,
      'type' => 'yandex#map',
      'behaviors' => ['scrollZoom', 'dblClickZoom', 'drag'],
    ];

    $this->map['display_options'] = [
      'display_type' => 'map',
      'open_button_text' => 'Open map',
      'close_button_text' => 'Close map',
      'width' => '100%',
      'height' => '400px',
    ];

    $this->map['controls'] = 1;
    $this->map['edit'] = TRUE;
  }

}
