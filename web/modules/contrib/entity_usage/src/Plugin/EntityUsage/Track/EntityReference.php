<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_usage\EntityUsage;
use Drupal\entity_usage\EntityUsageTrackBase;
use Drupal\entity_usage\EntityUsageTrackInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tracks usage of entities related in entity_reference fields.
 *
 * @EntityUsageTrack(
 *   id = "entity_reference",
 *   label = @Translation("Entity Reference Fields"),
 *   description = @Translation("Tracks usage of entities related in entity_reference fields."),
 * )
 */
class EntityReference extends EntityUsageTrackBase {

  /**
   * Entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs display plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\entity_usage\EntityUsage $usage_service
   *   The usage tracking service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The EntityFieldManager service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The EntityTypeManager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityUsage $usage_service,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $usage_service);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_usage.usage'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function trackOnEntityCreation(ContentEntityInterface $entity) {
    foreach ($this->entityReferenceFieldsAvailable($entity) as $field_name) {
      if (!$entity->$field_name->isEmpty()) {
        /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $field_item */
        foreach ($entity->$field_name as $field_item) {
          // This item got added. Track the usage up.
          $this->incrementEntityReferenceUsage($entity, $field_name, $field_item->target_id);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackOnEntityUpdate(ContentEntityInterface $entity) {
    // The assumption here is that an entity that is referenced by any
    // translation of another entity should be tracked, and only once
    // (regardless if many translations point to the same entity). So the
    // process to identify them is quite simple: we build a list of all entity
    // ids referenced before the update by all translations (original included),
    // and compare it with the list of ids referenced by all translations after
    // the update.
    $translations = [];
    $originals = [];
    $languages = $entity->getTranslationLanguages();
    foreach ($languages as $langcode => $language) {
      if (!$entity->hasTranslation($langcode)) {
        continue;
      }
      $translations[] = $entity->getTranslation($langcode);
      if (!$entity->original->hasTranslation($langcode)) {
        continue;
      }
      $originals[] = $entity->original->getTranslation($langcode);
    }

    foreach ($this->entityReferenceFieldsAvailable($entity) as $field_name) {
      $current_target_ids = [];
      foreach ($translations as $translation) {
        if (!$translation->{$field_name}->isEmpty()) {
          foreach ($translation->{$field_name} as $field_item) {
            $current_target_ids[] = $field_item->target_id;
          }
        }
      }
      $original_target_ids = [];
      foreach ($originals as $original) {
        if (!$original->{$field_name}->isEmpty()) {
          foreach ($original->{$field_name} as $field_item) {
            $original_target_ids[] = $field_item->target_id;
          }
        }
      }
      // If more than one translation references the same target entity, we
      // record only one usage.
      $original_target_ids = array_unique($original_target_ids);
      $current_target_ids = array_unique($current_target_ids);

      $added_ids = array_diff($current_target_ids, $original_target_ids);
      $removed_ids = array_diff($original_target_ids, $current_target_ids);

      foreach ($added_ids as $id) {
        $this->incrementEntityReferenceUsage($entity, $field_name, $id);
      }
      foreach ($removed_ids as $id) {
        $this->decrementEntityReferenceUsage($entity, $field_name, $id);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackOnEntityDeletion(ContentEntityInterface $entity) {
    $translations = [];
    // When deleting the main (untranslated) entity, loop over all translations
    // as well to release referenced entities there too.
    if ($entity === $entity->getUntranslated()) {
      $languages = $entity->getTranslationLanguages();
      foreach ($languages as $langcode => $language) {
        if (!$entity->hasTranslation($langcode)) {
          continue;
        }
        $translations[] = $entity->getTranslation($langcode);
      }
    }
    else {
      // Otherwise, this is a single translation being deleted, so we just need
      // to release usage reflected here.
      $translations = [$entity];
    }

    foreach ($this->entityReferenceFieldsAvailable($entity) as $field_name) {
      foreach ($translations as $translation) {
        if (!$translation->{$field_name}->isEmpty()) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $field_item */
          foreach ($translation->{$field_name} as $field_item) {
            // This item got deleted. Track the usage down.
            $this->decrementEntityReferenceUsage($entity, $field_name, $field_item->target_id);
          }
        }
      }
    }
  }

  /**
   * Retrieve the entity_reference fields on a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   An array of field_names that could reference to other content entities.
   */
  private function entityReferenceFieldsAvailable(ContentEntityInterface $entity) {
    $return_fields = [];
    $fields_on_entity = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    $entityref_fields_on_this_entity_type = [];
    if (!empty($this->entityFieldManager->getFieldMapByFieldType('entity_reference')[$entity->getEntityTypeId()])) {
      $entityref_fields_on_this_entity_type = $this->entityFieldManager->getFieldMapByFieldType('entity_reference')[$entity->getEntityTypeId()];
    }
    $entityref_on_this_bundle = array_intersect_key($fields_on_entity, $entityref_fields_on_this_entity_type);
    // Clean out basefields.
    $basefields = $this->entityFieldManager->getBaseFieldDefinitions($entity->getEntityTypeId());
    $entityref_on_this_bundle = array_diff_key($entityref_on_this_bundle, $basefields);
    if (!empty($entityref_on_this_bundle)) {
      // Make sure we only leave the fields that are referencing content
      // entities.
      foreach ($entityref_on_this_bundle as $key => $entityref) {
        $target_type = $entityref_on_this_bundle[$key]->getItemDefinition()->getSettings()['target_type'];
        $entity_type = $this->entityTypeManager->getStorage($target_type)->getEntityType();
        if ($entity_type instanceof ConfigEntityTypeInterface) {
          unset($entityref_on_this_bundle[$key]);
        }
      }

      $return_fields = array_keys($entityref_on_this_bundle);
    }
    return $return_fields;
  }

  /**
   * Helper method to increment the usage in entity_reference fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The host entity object.
   * @param string $field_name
   *   The name of the entity_reference field, present in $entity.
   * @param int $target_id
   *   The id of the target entity.
   */
  private function incrementEntityReferenceUsage(ContentEntityInterface $entity, $field_name, $target_id) {
    /** @var \Drupal\field\Entity\FieldConfig $definition */
    $definition = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$field_name];
    $referenced_entity_type = $definition->getSetting('target_type');
    $this->usageService->add($target_id, $referenced_entity_type, $entity->id(), $entity->getEntityTypeId(), $this->pluginId);
  }

  /**
   * Helper method to decrement the usage in entity_reference fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The host entity object.
   * @param string $field_name
   *   The name of the entity_reference field, present in $entity.
   * @param int $target_id
   *   The id of the target entity.
   */
  private function decrementEntityReferenceUsage(ContentEntityInterface $entity, $field_name, $target_id) {
    /** @var \Drupal\field\Entity\FieldConfig $definition */
    $definition = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle())[$field_name];
    $referenced_entity_type = $definition->getSetting('target_type');
    $this->usageService->delete($target_id, $referenced_entity_type, $entity->id(), $entity->getEntityTypeId());
  }

}
