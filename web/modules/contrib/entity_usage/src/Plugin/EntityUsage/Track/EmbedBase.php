<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\entity_usage\EmbedTrackInterface;
use Drupal\entity_usage\EntityUsage;
use Drupal\entity_usage\EntityUsageTrackBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for plugins tracking usage in entities embedded in WYSIWYG fields.
 */
abstract class EmbedBase extends EntityUsageTrackBase implements EmbedTrackInterface {

  /**
   * The ModuleHandler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The EntityRepository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The ModuleHandler service.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The EntityRepositoryInterface service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityUsage $usage_service,
    ModuleHandlerInterface $module_handler,
    EntityRepositoryInterface $entity_repository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $usage_service);
    $this->moduleHandler = $module_handler;
    $this->entityRepository = $entity_repository;
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
      $container->get('module_handler'),
      $container->get('entity.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function trackOnEntityCreation(ContentEntityInterface $entity) {
    $referenced_entities_by_field = $this->getEmbeddedEntitiesByField($entity);
    foreach ($referenced_entities_by_field as $field => $embedded_entities) {
      foreach ($embedded_entities as $uuid => $type) {
        // Increment the usage as embedded entity.
        $this->incrementEmbeddedUsage($entity, $type, $uuid);
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

    $current_uuids = [];
    foreach ($translations as $translation) {
      $current_uuids += $this->getEmbeddedEntitiesByField($translation, TRUE);
    }
    $original_uuids = [];
    foreach ($originals as $original) {
      $original_uuids += $this->getEmbeddedEntitiesByField($original, TRUE);
    }

    $added_uuids = array_diff_key($current_uuids, $original_uuids);
    $removed_uuids = array_diff_key($original_uuids, $current_uuids);

    foreach ($added_uuids as $uuid => $type) {
      $this->incrementEmbeddedUsage($entity, $type, $uuid);
    }
    foreach ($removed_uuids as $uuid => $type) {
      $this->decrementEmbeddedUsage($entity, $type, $uuid);
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

    foreach ($translations as $translation) {
      $referenced_entities_by_field = $this->getEmbeddedEntitiesByField($translation);
      foreach ($referenced_entities_by_field as $field => $embedded_entities) {
        foreach ($embedded_entities as $uuid => $type) {
          // Decrement the usage as embedded entity.
          $this->decrementEmbeddedUsage($entity, $type, $uuid);
        }
      }
    }

  }

  /**
   * Finds all entities embedded (<drupal-entity>) by formatted text fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   An entity object whose fields to analyze.
   * @param bool $omit_field_names
   *   (Optional) Whether the field names should be omitted from the results.
   *   Defaults to FALSE.
   *
   * @return array
   *   An array of found embedded entities, in the following structure:
   *   [
   *     'field_name' => [
   *       'uuid1' => 'entity_type1',
   *       'uuid2' => 'entity_type1',
   *       'uuid3' => 'entity_type2',
   *        etc.
   *     ],
   *   ]
   *   If the $omit_field_names flag is TRUE, the first level is not present,
   *   and the result array is directly an associative array of uuids as keys
   *   and entity_types as values.
   */
  protected function getEmbeddedEntitiesByField(ContentEntityInterface $entity, $omit_field_names = FALSE) {
    $entities = [];

    if ($this->moduleHandler->moduleExists('editor')) {
      $formatted_text_fields = _editor_get_formatted_text_fields($entity);
      foreach ($formatted_text_fields as $formatted_text_field) {
        $text = '';
        $field_items = $entity->get($formatted_text_field);
        foreach ($field_items as $field_item) {
          $text .= $field_item->value;
          if ($field_item->getFieldDefinition()->getType() == 'text_with_summary') {
            $text .= $field_item->summary;
          }
        }
        if ($omit_field_names) {
          $entities += $this->parseEntitiesFromText($text);
        }
        else {
          $entities[$formatted_text_field] = $this->parseEntitiesFromText($text);
        }
      }
    }

    return $entities;
  }

  /**
   * Helper method to increment the usage for embedded entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The host entity object.
   * @param string $t_type
   *   The type of the target entity.
   * @param string $uuid
   *   The UUID of the target entity.
   */
  protected function incrementEmbeddedUsage(ContentEntityInterface $entity, $t_type, $uuid) {
    $target_entity = $this->entityRepository->loadEntityByUuid($t_type, $uuid);
    if ($target_entity) {
      $this->usageService->add($target_entity->id(), $t_type, $entity->id(), $entity->getEntityTypeId(), $this->pluginId);
    }
  }

  /**
   * Helper method to decrement the usage for embedded entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The host entity object.
   * @param string $t_type
   *   The type of the target entity.
   * @param string $uuid
   *   The UUID of the target entity.
   */
  protected function decrementEmbeddedUsage(ContentEntityInterface $entity, $t_type, $uuid) {
    $target_entity = $this->entityRepository->loadEntityByUuid($t_type, $uuid);
    if ($target_entity) {
      $this->usageService->delete($target_entity->id(), $t_type, $entity->id(), $entity->getEntityTypeId());
    }
  }

}
