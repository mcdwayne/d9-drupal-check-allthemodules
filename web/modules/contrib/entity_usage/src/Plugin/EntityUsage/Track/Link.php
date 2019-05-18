<?php

namespace Drupal\entity_usage\Plugin\EntityUsage\Track;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\entity_usage\EntityUsage;
use Drupal\entity_usage\EntityUsageTrackBase;
use Drupal\link\LinkItemInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tracks usage of entities related in entity_reference fields.
 *
 * @EntityUsageTrack(
 *   id = "link",
 *   label = @Translation("Link Field References"),
 *   description = @Translation("Tracks usage of entities related in link fields."),
 * )
 */
class Link extends EntityUsageTrackBase {

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
    foreach ($this->linkFieldsAvailable($entity) as $field_name) {
      if ($entity->hasField($field_name) && !$entity->{$field_name}->isEmpty()) {
        /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $field_item */
        foreach ($entity->{$field_name} as $field_item) {
          // This item got added. Track the usage up.
          $target_entity = $this->getTargetEntity($field_item);
          if ($target_entity) {
            list($target_type, $target_id) = explode('|', $target_entity);
            $this->usageService->add($target_id, $target_type, $entity->id(), $entity->getEntityTypeId(), $this->pluginId);
          }
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

    foreach ($this->linkFieldsAvailable($entity) as $field_name) {
      $current_targets = [];
      foreach ($translations as $translation) {
        if ($translation->hasField($field_name) && !$translation->{$field_name}->isEmpty()) {
          foreach ($translation->{$field_name} as $field_item) {
            $target_entity = $this->getTargetEntity($field_item);
            if ($target_entity) {
              $current_targets[] = $target_entity;
            }
          }
        }
      }
      $original_targets = [];
      foreach ($originals as $original) {
        if ($original->hasField($field_name) && !$original->{$field_name}->isEmpty()) {
          foreach ($original->{$field_name} as $field_item) {
            $target_entity = $this->getTargetEntity($field_item);
            if ($target_entity) {
              $original_targets[] = $target_entity;
            }
          }
        }
      }
      // If more than one translation references the same target entity, we
      // record only one usage.
      $original_targets = array_unique($original_targets);
      $current_targets = array_unique($current_targets);

      $added_ids = array_diff($current_targets, $original_targets);
      $removed_ids = array_diff($original_targets, $current_targets);

      foreach ($added_ids as $added_entity) {
        list($target_type, $target_id) = explode('|', $added_entity);
        $this->usageService->add($target_id, $target_type, $entity->id(), $entity->getEntityTypeId(), $this->pluginId);
      }
      foreach ($removed_ids as $removed_entity) {
        list($target_type, $target_id) = explode('|', $removed_entity);
        $this->usageService->delete($target_id, $target_type, $entity->id(), $entity->getEntityTypeId());
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

    foreach ($this->linkFieldsAvailable($entity) as $field_name) {
      foreach ($translations as $translation) {
        if ($translation->hasField($field_name) && !$translation->{$field_name}->isEmpty()) {
          /** @var \Drupal\link\Plugin\Field\FieldType\LinkItem $field_item */
          foreach ($translation->{$field_name} as $field_item) {
            // This item got deleted. Track the usage down.
            $target_entity = $this->getTargetEntity($field_item);
            if ($target_entity) {
              list($target_type, $target_id) = explode('|', $target_entity);
              $this->usageService->delete($target_id, $target_type, $entity->id(), $entity->getEntityTypeId());
            }
          }
        }
      }
    }
  }

  /**
   * Retrieve the link fields on a given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity object.
   *
   * @return array
   *   An array of field_names that could reference to other content entities.
   */
  private function linkFieldsAvailable(ContentEntityInterface $entity) {
    $return_fields = [];
    $fields_on_entity = $this->entityFieldManager->getFieldDefinitions($entity->getEntityTypeId(), $entity->bundle());
    $link_fields_on_this_entity_type = [];
    if (!empty($this->entityFieldManager->getFieldMapByFieldType('link')[$entity->getEntityTypeId()])) {
      $link_fields_on_this_entity_type = $this->entityFieldManager->getFieldMapByFieldType('link')[$entity->getEntityTypeId()];
    }
    $link_fields_on_this_bundle = array_intersect_key($fields_on_entity, $link_fields_on_this_entity_type);
    // Clean out basefields.
    $basefields = $this->entityFieldManager->getBaseFieldDefinitions($entity->getEntityTypeId());
    $link_fields_on_this_bundle = array_diff_key($link_fields_on_this_bundle, $basefields);
    if (!empty($link_fields_on_this_bundle)) {
      $return_fields = array_keys($link_fields_on_this_bundle);
    }
    return $return_fields;
  }

  /**
   * Gets the target entity of a link item.
   *
   * @param \Drupal\link\LinkItemInterface $link
   *   The LinkItem to get the target from.
   *
   * @return string|null
   *   Target Type and ID glued together with a '|' or NULL if no entity linked.
   */
  private function getTargetEntity(LinkItemInterface $link) {
    // Check if LinkItem is linking to an entity.
    $url = $link->getUrl();
    if (!$url->isRouted() || !preg_match('/^entity\./', $url->getRouteName())) {
      return NULL;
    }

    // Ge the target entity type and ID.
    $route_parameters = $url->getRouteParameters();
    $target_type = array_keys($route_parameters)[0];
    $target_id = $route_parameters[$target_type];

    if (!($this->entityTypeManager->getDefinition($target_type) instanceof ContentEntityTypeInterface)) {
      // This module only supports content entity types.
      return NULL;
    }

    // Glue the target type and ID together for easy comparison.
    return $target_type . '|' . $target_id;
  }

}
