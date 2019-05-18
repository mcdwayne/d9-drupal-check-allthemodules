<?php

namespace Drupal\field_collection_template\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'field_collection_template_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_collection_template_formatter",
 *   label = @Translation("Template for field collection items"),
 *   field_types = {
 *     "field_collection"
 *   }
 * )
 */
class FieldCollectionTemplateFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $entity = $items->getEntity();
    $elements = [];
    foreach ($items as $delta => $item) {
      if ($item->value !== NULL) {
        $elements[$delta] = array(
          '#theme' => 'field_collection_template_formatter',
          '#item' => $item,
          '#content' => \Drupal::entityTypeManager()->getViewBuilder('field_collection_item')->view($item->getFieldCollectionItem()),
          '#index' => $delta + 1,
          '#entity' => $entity,
        );
      }
    }
    return $elements;
  }

}
