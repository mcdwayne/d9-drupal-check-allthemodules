<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\field_handler;

use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\Plugin\FieldHandlerBase;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\drupal_content_sync\SyncIntent;

/**
 * Providing an implementation for the "path" field type of content entities.
 *
 * @FieldHandler(
 *   id = "drupal_content_sync_default_path_handler",
 *   label = @Translation("Default Path"),
 *   weight = 90
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\field_handler
 */
class DefaultPathHandler extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public static function supports($entity_type, $bundle, $field_name, FieldDefinitionInterface $field) {
    return $field->getType() == "path";
  }

  /**
   * @inheritdoc
   */
  public function export(ExportIntent $intent) {
    $action = $intent->getAction();
    $entity = $intent->getEntity();

    if ($this->settings['export'] != ExportIntent::EXPORT_AUTOMATICALLY) {
      return FALSE;
    }

    // Deletion doesn't require any action on field basis for static data.
    if ($action == SyncIntent::ACTION_DELETE) {
      return FALSE;
    }

    $value = $entity->get($this->fieldName)->getValue();
    if (!empty($value)) {
      unset($value[0]['pid']);
      unset($value[0]['source']);
      $intent->setField($this->fieldName, $value);
    }

    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function import(ImportIntent $intent) {
    return parent::import($intent);
  }

}
