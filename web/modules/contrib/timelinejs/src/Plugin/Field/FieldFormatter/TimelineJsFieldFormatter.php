<?php

namespace Drupal\timelinejs\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\timelinejs\TimelineJS;

/**
 * Plugin implementation of the 'timeline_js_field_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "timeline_js_field_formatter",
 *   label = @Translation("TimelineJS"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class TimelineJsFieldFormatter extends EntityReferenceFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    /* @var \Drupal\timelinejs\Entity\TimelineInterface $entity */
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      /* @var $referenced_user \Drupal\user\UserInterface */
      $renderableTimelineJS = new TimelineJS($entity);
      $elements[$delta] = $renderableTimelineJS->toRenderable();
    }

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $targetEntityType = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    return $targetEntityType === 'timeline' && parent::isApplicable($field_definition);
  }

}
