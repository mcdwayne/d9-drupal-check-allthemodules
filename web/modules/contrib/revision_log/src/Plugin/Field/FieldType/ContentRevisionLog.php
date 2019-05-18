<?php

namespace Drupal\revision_log\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Plugin implementation of the 'content_revision_log' field type.
 *
 * @FieldType(
 *   id = "content_revision_log",
 *   label = @Translation("Revision log"),
 *   description = @Translation("Revision Log for modified fields"),
 *   default_formatter = "revision_log_formatter",
 *   no_ui = true
 * )
 */
class ContentRevisionLog extends FieldItemBase {


  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition){
    return [];
  }
  
  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition){
    return [];
  }

}
