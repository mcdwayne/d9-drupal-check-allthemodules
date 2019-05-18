<?php

namespace Drupal\cards\Plugin\Field\FieldType;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\entityreference_view_mode\Plugin\Field\FieldType\EntityReferenceViewMode;
use Drupal\entityreference_view_mode\Plugin\Field\FieldType\EntityReferenceViewModeFieldType;

/**
 * Plugin implementation of the 'field_content_view' field type.
 *
 * @FieldType(
 *   id = "card_children_field_type",
 *   label = @Translation("Cards Children"),
 *   module = "cards",
 *   description = @Translation("Field referencing a piece of content and an associated view mode."),
 *   default_widget = "card_children_field_widget",
 *   default_formatter = "card_children_field_formatter"
 * )
 */
class CardChildrenFieldType extends CardFieldType {

}
