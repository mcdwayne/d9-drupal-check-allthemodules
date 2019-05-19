<?php
namespace Drupal\spatialfields\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'spatialfields_geom' formatter.
 *
 * @FieldFormatter(
 *   id = "spatialformatter",
 *   module = "spatialfields",
 *   label = @Translation("Simple text-based formatter"),
 *   field_types = {
 *     "spatialfields_geom",
 *   }
 * )
 */
class SpatialFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    
    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => [
          'class' => 'spatialfield wkt-geom',
        ],
        '#value' =>  $item->value,
      ];
    }
    return $elements;
  }

}