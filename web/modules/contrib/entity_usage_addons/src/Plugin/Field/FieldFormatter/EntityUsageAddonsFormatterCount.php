<?php

namespace Drupal\entity_usage_addons\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\file\Plugin\Field\FieldFormatter\BaseFieldFileFormatterBase;

/**
 * Formatter that shows the entity usage as a count.
 *
 * @FieldFormatter(
 *   id = "entity_usage_addons_formatter_count",
 *   label = @Translation("Entity Usage - Count"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class EntityUsageAddonsFormatterCount extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $entityType = $item->getEntity()->getEntityType()->id();
    $entityId = $item->value;

    // TODO Dependency Inject.
    $entityUsageAddons = \Drupal::service('entity_usage_addons.usage');
    return $entityUsageAddons->linkedUsage($entityType, $entityId);
  }

}
