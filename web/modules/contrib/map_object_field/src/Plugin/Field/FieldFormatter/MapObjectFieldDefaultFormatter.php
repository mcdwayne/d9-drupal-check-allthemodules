<?php
namespace Drupal\map_object_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'map_object_field_default' formatter.
 *
 * @FieldFormatter(
 *   id = "map_object_field_default",
 *   label = @Translation("Map Object Field default"),
 *   field_types = {
 *     "map_object_field"
 *   }
 * )
 */
class MapObjectFieldDefaultFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    /** @var \Drupal\map_object_field\MapObject\MapObjectService $map_object_service */
    $map_object_service = \Drupal::service('map_object.service');
    foreach ($items as $delta => $item) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $item->getEntity();
      $map_object_field_data = $map_object_service->getMapObjectsByFieldDataAsString(
        $entity->getEntityType()->id(),
        $entity->id(),
        $entity->getEntityType()->isRevisionable() ? $entity->getRevisionId() : $entity->id(),
        $delta
      );
      $element = [
        '#theme' => 'map_default_format',
        '#name' => $item->map_object_name,
        '#overlays' => $map_object_field_data,
        '#mapOptions' => [
          'center-lat' => $item->map_center_lat,
          'center-lng' => $item->map_center_lng,
          'zoom' => $item->map_zoom,
          'map-type' => $item->map_type,
        ],
        '#mapWidth' => $this->getSetting('map_width'),
        '#mapHeight' => $this->getSetting('map_height'),
      ];
      /** @var \Drupal\map_object_field\Service\MapObjectLibInterface $map_bject_field_lib */
      $map_bject_field_lib = \Drupal::service('map_object_field_lib');
      foreach ($map_bject_field_lib->getLibrariesForFormatter() as $lib) {
        $element['#attached']['library'][] = $lib;
      }
      $elements[$delta] = $element;
    }
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'map_width' => '100%',
      'map_height' => '400px',
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);
    $settings = $this->getSettings();

    $elements['map_width'] = [
      '#title' => $this->t('Map width'),
      '#type' => 'textfield',
      '#default_value' => isset($settings['map_width']) ? $settings['map_width'] : '100%',
      '#size' => 10,
    ];

    $elements['map_height'] = [
      '#title' => $this->t('Map height'),
      '#type' => 'textfield',
      '#default_value' => isset($settings['map_height']) ? $settings['map_height'] : '400px',
      '#size' => 10,
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $summary[] = $this->t(
      'Map width: @map_width',
      ['@map_width' => $settings['map_width']]
    );
    $summary[] = $this->t(
      'Map height: @map_height',
      ['@map_height' => $settings['map_height']]
    );
    return $summary;
  }

}
