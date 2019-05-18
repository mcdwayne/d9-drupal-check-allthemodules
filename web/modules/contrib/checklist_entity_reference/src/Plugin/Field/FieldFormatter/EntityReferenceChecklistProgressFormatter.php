<?php

namespace Drupal\checklist_entity_reference\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;

/**
 * Plugin implementation of the 'entity reference checklist' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_checklist_progress",
 *   label = @Translation("Checklist Progress"),
 *   description = @Translation("Display the progress of the referenced entities checklist."),
 *   field_types = {
 *     "entity_reference_checklist"
 *   }
 * )
 */
class EntityReferenceChecklistProgressFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $selected = 0;
    if (!$items->isEmpty()) {
      $selected = $items->count();
    }

    $settings = $items->getDataDefinition()->getSettings();
    $handler = explode(':', $settings['handler']);
    $bundle_key = \Drupal::entityTypeManager()->getDefinition($handler[1])->getKey('bundle');
    $entities = \Drupal::entityTypeManager()->getStorage($handler[1])->loadByProperties([
      $bundle_key => $settings['handler_settings']['target_bundles'],
    ]);
    $total = count($entities);

    $elements[0] = [
      '#plain_text' => $selected . ' / ' . $total,
    ];

    return $elements;
  }

}
