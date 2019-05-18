<?php

namespace Drupal\entity_split\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Component\Serialization\Json;

/**
 * Defines the Entity split entity.
 *
 * @ingroup entity_split
 *
 * @ContentEntityType(
 *   id = "entity_split",
 *   label = @Translation("Entity split"),
 *   bundle_label = @Translation("Entity split type"),
 *   handlers = {
 *     "storage_schema" = "Drupal\entity_split\EntitySplitStorageSchema",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\entity_split\Entity\EntitySplitViewsData",
 *     "translation" = "Drupal\entity_split\EntitySplitTranslationHandler",
 *
 *     "form" = {
 *       "default" = "Drupal\entity_split\Form\EntitySplitForm",
 *       "add" = "Drupal\entity_split\Form\EntitySplitForm",
 *       "edit" = "Drupal\entity_split\Form\EntitySplitForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "access" = "Drupal\entity_split\EntitySplitAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\entity_split\EntitySplitHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "entity_split",
 *   data_table = "entity_split_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer entity split types",
 *   entity_keys = {
 *     "id" = "id",
 *     "bundle" = "type",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "edit-form" = "/entity_split/{entity_split}",
 *   },
 *   bundle_entity_type = "entity_split_type",
 *   field_ui_base_route = "entity.entity_split_type.edit_form"
 * )
 */
class EntitySplit extends ContentEntityBase implements EntitySplitInterface {

  /**
   * {@inheritdoc}
   */
  public function label() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $master_entity */
    $master_entity = $this->get('entity_id')->entity;
    $langcode = $this->language()->getId();

    if ($master_entity && $master_entity->hasTranslation($langcode)) {
      $master_entity = $master_entity->getTranslation($langcode);
    }

    return $master_entity ? $master_entity->label() : '';
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The entity type to which this split is attached.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity to which this split is attached.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    if ($entity_split_type = EntitySplitType::load($bundle)) {
      $fields['entity_id'] = clone $base_field_definitions['entity_id'];
      $fields['entity_id']->setSetting('target_type', $entity_split_type->getMasterEntityType());
      return $fields;
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterEntity() {
    return $this->get('entity_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterEntityType() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMasterEntityId() {
    return $this->get('entity_id')->target_id;
  }

  /**
   * Loads entity split entities for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\entity_split\Entity\EntitySplitInterface[]
   *   List of entity splits attached to the entity.
   */
  private static function loadEntitySplitsForEntity(ContentEntityInterface $entity) {
    $entity_split_types = EntitySplitType::getEntitySplitTypesForEntity($entity);

    if (empty($entity_split_types)) {
      return [];
    }

    $query = \Drupal::entityQuery('entity_split');
    $query->condition('type', array_keys($entity_split_types));
    $query->condition('entity_id', $entity->id());
    $query->accessCheck(FALSE);

    $entity_ids = $query->execute();

    return !empty($entity_ids) ? static::loadMultiple($entity_ids) : [];
  }

  /**
   * {@inheritdoc}
   */
  public static function getEntitySplitsForEntity(ContentEntityInterface $entity) {
    $entity_splits = static::loadEntitySplitsForEntity($entity);

    if (empty($entity_splits)) {
      return [];
    }

    $langcode = $entity->language()->getId();

    $entity_splits_by_type = [];

    foreach ($entity_splits as $entity_split) {
      if ($entity_split->hasTranslation($langcode)) {
        $entity_split = $entity_split->getTranslation($langcode);
      }
      $entity_splits_by_type[$entity_split->bundle()] = $entity_split;
    }

    ksort($entity_splits_by_type);

    return $entity_splits_by_type;
  }

  /**
   * {@inheritdoc}
   */
  public static function deleteEntitySplitsForEntity(ContentEntityInterface $entity) {
    foreach (static::loadEntitySplitsForEntity($entity) as $entity_split) {
      $entity_split->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function createRequiredEntitySplitsForEntity(ContentEntityInterface $entity) {
    /** @var \Drupal\entity_split\Entity\EntitySplitTypeInterface[] $entity_split_types */
    $entity_split_types = EntitySplitType::getEntitySplitTypesForEntity($entity);

    if (empty($entity_split_types)) {
      return;
    }

    $entity_splits = static::loadEntitySplitsForEntity($entity);

    /** @var \Drupal\entity_split\Entity\EntitySplit[] $entity_splits_by_type */
    $entity_splits_by_type = [];

    foreach ($entity_splits as $entity_split) {
      $entity_splits_by_type[$entity_split->bundle()] = $entity_split;
    }

    foreach ($entity_split_types as $entity_split_type_id => $entity_split_type) {
      if (!isset($entity_splits_by_type[$entity_split_type_id])) {
        // Entity splits do not exist for the entity.
        static::createEntitySplit($entity, $entity_split_type);
      }
      elseif ($entity_split_type->isTranslatableBundle()) {
        // Entity splits exist for the entity, check their translations.
        $entity_split = $entity_splits_by_type[$entity_split_type_id];
        $entity_split_changed = FALSE;

        if ($entity_split->getUntranslated()->language()->getId() === LanguageInterface::LANGCODE_NOT_SPECIFIED) {
          // Entity split is not translatable.
          continue;
        }

        // Check translation matching.
        foreach ($entity->getTranslationLanguages() as $langcode => $language) {
          if (!$entity_split->hasTranslation($langcode) && !$language->isLocked()) {
            $translation_values = $entity_split->toArray();
            $translation_values['content_translation_source'] = $entity_split->getUntranslated()->language()->getId();
            $entity_split->addTranslation($langcode, $translation_values);
            $entity_split_changed = TRUE;
          }
        }
        foreach ($entity_split->getTranslationLanguages(FALSE) as $langcode => $language) {
          if (!$entity->hasTranslation($langcode) && !$language->isLocked()) {
            $entity_split->removeTranslation($langcode);
            $entity_split_changed = TRUE;
          }
        }

        if ($entity_split_changed) {
          $entity_split->save();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTagsToInvalidate() {
    $master_entity = $this->getMasterEntity();
    $master_entity_tags = !empty($master_entity) ? $master_entity->getCacheTagsToInvalidate() : [];

    return Cache::mergeTags(parent::getCacheTagsToInvalidate(), $master_entity_tags);
  }

  /**
   * {@inheritdoc}
   */
  protected function invalidateTagsOnSave($update) {
    parent::invalidateTagsOnSave($update);

    if (!$update) {
      // An entity was created, also invalidate the master entity cache tags.
      $master_entity = $this->getMasterEntity();

      if (!empty($master_entity)) {
        Cache::invalidateTags($master_entity->getCacheTagsToInvalidate());
      }
    }
  }

  /**
   * Creates entity split entity for the entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param \Drupal\entity_split\Entity\EntitySplitTypeInterface $entity_split_type
   *   The entity.
   *
   * @return \Drupal\entity_split\Entity\EntitySplitInterface
   *   Created entity split entity.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   In case of failures an exception is thrown.
   */
  private static function createEntitySplit(ContentEntityInterface $entity, EntitySplitTypeInterface $entity_split_type) {
    // Inherit default language from the master entity.
    $create_translations = $entity_split_type->isTranslatableBundle();
    $default_langcode = $create_translations ? $entity->getUntranslated()->language()->getId() : LanguageInterface::LANGCODE_NOT_SPECIFIED;

    $values = [
      'type' => $entity_split_type->id(),
      'entity_type' => $entity->getEntityTypeId(),
      'entity_id' => $entity->id(),
      'langcode' => $default_langcode,
    ];

    $entity_split = static::create($values);

    if ($create_translations) {
      foreach (array_keys($entity->getTranslationLanguages(FALSE)) as $langcode) {
        $translation_values = $entity_split->toArray();
        $translation_values['content_translation_source'] = $default_langcode;
        $entity_split->addTranslation($langcode, $translation_values);
      }
    }

    $entity_split->save();

    return $entity_split;
  }

  /**
   * {@inheritdoc}
   */
  public static function alterEntityForm(array &$form, ContentEntityInterface $entity, $langcode) {
    $entity_split_types = EntitySplitType::getEntitySplitTypesForEntity($entity);

    if (empty($entity_split_types)) {
      return;
    }

    $translations = $entity->getTranslationLanguages();

    if ($entity->isTranslatable() && !isset($translations[$langcode])) {
      return;
    }

    $entity_splits_by_type = static::getEntitySplitsForEntity($entity);

    foreach ($entity_split_types as $entity_split_type_id => $entity_split_type) {
      if (!isset($entity_splits_by_type[$entity_split_type_id])) {
        // Entity splits do not exist for the entity.
        $entity_splits_by_type[$entity_split_type_id] = static::createEntitySplit($entity, $entity_split_type);
      }
    }

    foreach ($entity_splits_by_type as $bundle => $entity_split) {
      $entity_split_type = EntitySplitType::load($bundle);

      if ($entity_split_type->isTranslatableBundle() && !$entity_split->hasTranslation($langcode)
          && ($entity_split->getUntranslated()->language()->getId() !== LanguageInterface::LANGCODE_NOT_SPECIFIED)
          && ($langcode !== LanguageInterface::LANGCODE_NOT_SPECIFIED) && ($langcode !== LanguageInterface::LANGCODE_NOT_APPLICABLE)) {
        $translation_values = $entity_split->toArray();
        $translation_values['content_translation_source'] = $entity_split->getUntranslated()->language()->getId();
        $entity_split->addTranslation($langcode, $translation_values);
        $entity_split->save();
      }

      if ($entity_split->hasTranslation($langcode)) {
        $entity_split = $entity_split->getTranslation($langcode);
      }

      // Add link which opens modal window with entity form.
      $form['actions']['entity_split_' . $bundle] = [
        '#type' => 'link',
        '#url' => $entity_split->toUrl('edit-form'),
        '#title' => $entity_split_type->label(),
        '#attributes' => [
          'class' => ['button', 'use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 'auto',
          ]),
        ],
        '#weight' => 100,
      ];
    }
  }

}
