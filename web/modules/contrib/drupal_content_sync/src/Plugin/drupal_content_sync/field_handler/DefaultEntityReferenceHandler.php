<?php

namespace Drupal\drupal_content_sync\Plugin\drupal_content_sync\field_handler;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\drupal_content_sync\Entity\MetaInformation;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\drupal_content_sync\Plugin\FieldHandlerBase;
use Drupal\drupal_content_sync\SyncIntent;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Providing a minimalistic implementation for any field type.
 *
 * @FieldHandler(
 *   id = "drupal_content_sync_default_entity_reference_handler",
 *   label = @Translation("Default Entity Reference"),
 *   weight = 90
 * )
 *
 * @package Drupal\drupal_content_sync\Plugin\drupal_content_sync\field_handler
 */
class DefaultEntityReferenceHandler extends FieldHandlerBase {

  /**
   * {@inheritdoc}
   */
  public static function supports($entity_type, $bundle, $field_name, FieldDefinitionInterface $field) {
    if (!in_array($field->getType(), ["entity_reference", "entity_reference_revisions"])) {
      return FALSE;
    }

    $type = $field->getSetting('target_type');
    if (in_array($type, ['user', 'brick_type', 'paragraph'])) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Don't expose option, but force export.
   *
   * @return bool
   */
  protected function forceReferencedEntityExport() {
    return FALSE;
  }

  /**
   *
   */
  protected function shouldExportReferencedEntities() {
    return $this->forceReferencedEntityExport() || (isset($this->settings['handler_settings']['export_referenced_entities']) && $this->settings['handler_settings']['export_referenced_entities'] === 0 ? 0 : 1);
  }

  /**
   * @inheritdoc
   */
  public function getHandlerSettings() {
    $options = [];

    if (!$this->forceReferencedEntityExport()) {
      $options = [
        'export_referenced_entities' => [
          '#type' => 'checkbox',
          '#title' => 'Export referenced entities',
          '#default_value' => $this->shouldExportReferencedEntities(),
        ],
      ];
    }

    return $options;
  }

  /**
   *
   */
  protected function loadReferencedEntity(ImportIntent $intent, $definition) {
    return $intent->loadEmbeddedEntity($definition);
  }

  /**
   *
   */
  protected function setValues(ImportIntent $intent) {
    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     */
    $entity = $intent->getEntity();

    $data = $intent->getField($this->fieldName);

    $values = [];
    foreach ($data as $value) {
      $reference = $this->loadReferencedEntity($intent, $value);

      if ($reference) {
        $info = $intent->getEmbeddedEntityData($value);

        if ($this->fieldDefinition->getType() == 'entity_reference_revisions') {
          $attributes = [
            'target_id' => $reference->id(),
            'target_revision_id' => $reference->getRevisionId(),
          ];
        }
        else {
          $attributes = [
            'target_id' => $reference->id(),
          ];
        }

        $values[] = array_merge($info, $attributes);
      }
    }

    $entity->set($this->fieldName, $values);

    return TRUE;
  }

  /**
   *
   */
  protected function getTargetEntityType($entity) {
    $entityFieldManager = \Drupal::service('entity_field.manager');
    $field_definitions = $entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    /**
     * @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition
     */
    $field_definition = $field_definitions[$this->fieldName];

    $reference_type = $field_definition
      ->getFieldStorageDefinition()
      ->getPropertyDefinition('entity')
      ->getTargetDefinition()
      ->getEntityTypeId();

    return $reference_type;
  }

  /**
   * @inheritdoc
   */
  public function import(ImportIntent $intent) {
    $action = $intent->getAction();

    // Deletion doesn't require any action on field basis for static data.
    if ($action == SyncIntent::ACTION_DELETE) {
      return FALSE;
    }

    return $this->setValues($intent);
  }

  /**
   * @inheritdoc
   */
  public function export(ExportIntent $intent) {
    $action = $intent->getAction();
    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     */
    $entity = $intent->getEntity();

    // Deletion doesn't require any action on field basis for static data.
    if ($action == SyncIntent::ACTION_DELETE) {
      return FALSE;
    }

    $entityFieldManager = \Drupal::service('entity_field.manager');
    $field_definitions = $entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    /**
     * @var \Drupal\Core\Field\FieldDefinitionInterface $field_definition
     */
    $field_definition = $field_definitions[$this->fieldName];
    $entityTypeManager = \Drupal::entityTypeManager();
    $reference_type = $field_definition
      ->getFieldStorageDefinition()
      ->getPropertyDefinition('entity')
      ->getTargetDefinition()
      ->getEntityTypeId();
    $storage = $entityTypeManager
      ->getStorage($reference_type);

    $data = $entity->get($this->fieldName)->getValue();
    if (!$data && $this->fieldName == 'menu_link') {
      $menu_link_manager = \Drupal::service('plugin.manager.menu.link');
      $links = $menu_link_manager->loadLinksByRoute('entity.' . $entity->getEntityTypeId() . '.canonical', [$entity->getEntityTypeId() => $entity->id()]);
      $data = [];
      foreach ($links as $link) {
        $uuid   = $link->getDerivativeId();
        $item   = \Drupal::service('entity.repository')
          ->loadEntityByUuid('menu_link_content', $uuid);
        $data[] = [
          'target_id' => $item->id(),
        ];
      }
    }

    $result = [];

    foreach ($data as $delta => $value) {
      if (empty($value['target_id'])) {
        continue;
      }

      $target_id = $value['target_id'];
      $reference = $storage
        ->load($target_id);

      if (!$reference || $reference->uuid() == $intent->getUuid()) {
        continue;
      }

      unset($value['target_id']);

      $result[] = $this->serializeReference($intent, $reference, $value);
    }

    $intent->setField($this->fieldName, $result);

    return TRUE;
  }

  /**
   *
   */
  protected function serializeReference(ExportIntent $intent, FieldableEntityInterface $reference, $value) {
    foreach ($this->getInvalidExportSubfields() as $field) {
      unset($value[$field]);
    }
    if ($this->shouldExportReferencedEntities()) {
      return $intent->embedEntity($reference, TRUE, $value);
    }
    else {
      return $intent->embedEntityDefinition(
        $reference->getEntityTypeId(),
        $reference->bundle(),
        $reference->uuid(),
        FALSE,
        $value
      );
    }
  }

  /**
   *
   */
  protected function getInvalidExportSubfields() {
    return ['_accessCacheability', '_attributes', '_loaded', 'top', 'target_revision_id', 'subform'];
  }

  /**
   * Save the export settings the user selected for paragraphs.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   */
  public static function saveEmbeddedExportPools(FieldableEntityInterface $entity, $parent_entity = NULL, $tree_position = []) {
    if (!$parent_entity) {
      $parent_entity = $entity;
    }
    // Make sure paragraph export settings are saved as well..
    $entityTypeManager = \Drupal::entityTypeManager();
    $entityFieldManager = \Drupal::service('entity_field.manager');
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
    $fields = $entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    foreach ($fields as $name => $definition) {
      if ($definition->getType() == 'entity_reference_revisions') {
        $reference_type = $definition
          ->getFieldStorageDefinition()
          ->getPropertyDefinition('entity')
          ->getTargetDefinition()
          ->getEntityTypeId();
        $storage = $entityTypeManager
          ->getStorage($reference_type);

        $data = $entity->get($name)->getValue();
        foreach ($data as $delta => $value) {
          if (empty($value['target_id'])) {
            continue;
          }

          $target_id = $value['target_id'];
          $reference = $storage
            ->load($target_id);

          if (!$reference) {
            continue;
          }

          MetaInformation::saveSelectedExportPoolInfoForField($parent_entity, $name, $delta, $reference->getEntityTypeId(), $reference->bundle(), $reference->uuid(), $tree_position);

          self::saveEmbeddedExportPools($reference, $parent_entity, array_merge($tree_position, [$name, $delta, 'subform']));
        }
      }
    }
  }

}
