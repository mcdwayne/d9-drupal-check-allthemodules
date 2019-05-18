<?php

/**
 * @file
 * Contains \Drupal\entity_reference_inline\Plugin\Field\FieldType\EntityReferenceInlineItem.
 */

namespace Drupal\entity_reference_inline\Plugin\Field\FieldType;

use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Defines the 'entity_reference_inline' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 *
 * @FieldType(
 *   id = "entity_reference_inline",
 *   label = @Translation("Entity reference inline"),
 *   description = @Translation("An entity field containing an entity reference for inline editing."),
 *   category = @Translation("Reference inline"),
 *   default_widget = "entity_reference_inline",
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\entity_reference_inline\Plugin\Field\FieldType\EntityReferenceInlineFieldItemList",
 * )
*/
class EntityReferenceInlineItem extends EntityReferenceItem implements EntityReferenceInlineItemInterface {

  use FieldItemCommonMethodsTrait;

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityManager()->getDefinition($settings['target_type']);

    $properties['entity'] = DataReferenceDefinition::create('entity_inline')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', $settings['target_type']);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $values = FieldItemBase::getValue();
    if ($include_computed || $this->hasNewEntity()) {
      $values['entity'] = $this->entity;
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->skipPreSave) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityInterface $referenced_entity */
    $referenced_entity = $this->entity;

    $needs_save = FALSE;
    if ($this->referencedEntityNeedsSave()) {
      if ($referenced_entity instanceof TranslatableInterface) {
        $referenced_entity = $this->getFieldDefinition()->isTranslatable() && $referenced_entity->hasTranslation($this->getLangcode()) ? $referenced_entity->getTranslation($this->getLangcode()) : $referenced_entity;
      }

      $needs_save = TRUE;
    }
    // If we reference an entity, which has different ID or revision ID, then we
    // assume that this entity has been already saved and therefore we only need
    // to update the referenced metadata.
    // @todo in order to cover all the possible cases we need additionally an
    // entity method checking if the entity has unsaved changes.
    elseif (!$this->itemNeedsPropertiesUpdate()) {
      if (isset($referenced_entity->original) && ($unchanged = $referenced_entity->original) && isset($unchanged->loadedUnchanged)) {
        $unchanged = $referenced_entity->original;
      }
      else {
        $storage = \Drupal::entityTypeManager()->getStorage($referenced_entity->getEntityTypeId());
        // Get the entity id from the entity itself instead from the target_id
        // property, as if the entity set on the field item was new target_id
        // has been set to NULL, but meanwhile the entity might have been saved
        // elsewhere, but the target_id property will still not be set.
        /** @var \Drupal\Core\Entity\ContentEntityInterface $unchanged */
        $unchanged = $storage->loadUnchanged($referenced_entity->id());

        // Flag the entity object we are comparing that the entity has been
        // loaded through loadUnchanged in order for the referenced entities hold
        // in an inline entity field type to be loaded unchanged as well.
        // @see \Drupal\entity_reference_inline\Plugin\DataType\EntityReferenceInline::getTarget()
        $unchanged->loadedUnchanged = TRUE;

        // As we've already loaded the unchanged entity we attach it to the
        // current referenced entity for performance reasons, because otherwise
        // it will be loaded again in EntityStorageBase::doPreSave or in
        // ContentEntityBase::hasTranslationChanges.
        $referenced_entity->original = $unchanged;
      }

      // We save the entity only in case it has changed in order to speed up
      // the save process of big structures.
      //
      // A fast check for addition or removal of an entity translation.
      $current_translation_langcodes = array_keys($referenced_entity->getTranslationLanguages());
      $previous_translation_langcodes = array_keys($unchanged->getTranslationLanguages());
      sort($current_translation_langcodes);
      sort($previous_translation_langcodes);
      if ($current_translation_langcodes != $previous_translation_langcodes) {
        if ($this->getFieldDefinition()->isTranslatable() && $referenced_entity->hasTranslation($this->getLangcode())) {
          $referenced_entity = $referenced_entity->getTranslation($this->getLangcode());
        }
        $referenced_entity->inlineEditedHasTranslationChanges = TRUE;
        $needs_save = TRUE;
      }
      else {
        // If the entity has been edited through our inline widget then it
        // might have been flagged with the translation language in which it
        // has been edited, so we have to check only this translation for
        // changes.
        // @see \Drupal\entity_reference_inline\Plugin\Field\FieldWidget\EntityReferenceInlineWidget::buildEntity()
        if (isset($referenced_entity->inlineEditedLangcode) && ($inline_edited_langcode = $referenced_entity->inlineEditedLangcode) && $referenced_entity->hasTranslation($inline_edited_langcode)) {
          $referenced_entity = $referenced_entity->getTranslation($inline_edited_langcode);
          if ($referenced_entity->hasTranslationChanges()) {
            $referenced_entity->inlineEditedHasTranslationChanges = TRUE;
            $needs_save = TRUE;
          }
        }
        else {
          foreach ($current_translation_langcodes as $langcode) {
            /** @var \Drupal\Core\Entity\ContentEntityInterface $translation */
            $translation = $referenced_entity->getTranslation($langcode);
            if ($translation->hasTranslationChanges()) {
              if ($this->getFieldDefinition()->isTranslatable() && $referenced_entity->hasTranslation($this->getLangcode())) {
                $referenced_entity = $referenced_entity->getTranslation($this->getLangcode());
              }
              elseif ($translation->isNewTranslation()) {
                $referenced_entity = $translation;
              }
              $referenced_entity->inlineEditedHasTranslationChanges = TRUE;
              $needs_save = TRUE;
              break;
            }
          }
        }
      }
    }

    // In case we haven't found any changes we still have to check the
    // underlying structure for inconsistencies which require save of the
    // current entity.
    if ($needs_save || $this->needsSave(FALSE)) {
      // Flag the entity as being in a saving process. If there are inline
      // references on the entity as well then when calling
      // hasTranslationChanges on their item list we will append the original
      // entity and in order to not unset it and so reset the cache of the
      // Entity::hasTranslationChanges and also to not load later again
      // additionally the original again we will check if the parent is in a
      // saving process and if so do not unset the original entity, otherwise
      // we do unset it.
      // @see EntityReferenceRevisionsInlineFieldItemList::hasTranslationChanges
      $referenced_entity->inlineSaving = TRUE;

      $referenced_entity->save();
    }

    $this->entity = $referenced_entity;
  }

  /**
   * Checks whether the item needs property updates.
   *
   * This method checks if the property holding the entity ID matches the entity
   * ID on the entity object i.e. if the property is up to date. This might
   * happen if somehow the ID of the entity object is changed.
   *
   * @return bool
   */
  protected function itemNeedsPropertiesUpdate() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entity;
    if (!$entity) {
      return isset($this->target_id);
    }

    return $this->target_id != $entity->id();
  }

  /**
   * Checks whether the referenced entity needs save.
   *
   * @return bool
   */
  protected function referencedEntityNeedsSave() {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entity;
    if (!$entity) {
      return FALSE;
    }

    $needs_save = $entity->isNew() || $entity->isNewTranslation() || !empty($entity->needsSave);
    return $needs_save;
  }

  /**
   * {@inheritdoc}
   */
  public function needsSave($include_current_item = TRUE) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->entity;
    if (!$entity) {
      return FALSE;
    }

    $needs_save = $include_current_item ? ($this->needsSave || $this->referencedEntityNeedsSave() || $this->itemNeedsPropertiesUpdate()) : FALSE;
    if (!$needs_save) {
      foreach (array_keys($entity->getTranslationLanguages()) as $langcode) {
        $translation = $entity->getTranslation($langcode);
        if ($translation->isNewTranslation()) {
          // For performance reasons we set the flag on the item, so that in
          // case of a big structure not to iterate down the whole structure
          // till the bottom each time we go one level deeper.
          $this->needsSave = TRUE;
          return TRUE;
        }

        // We have to go down the structure and check if an entity down the
        // structure needs a save.
        $fields = $translation->isDefaultTranslation() ? $translation->getFields() : $translation->getTranslatableFields();
        foreach ($fields as $items) {
          if ($items instanceof EntityReferenceInlineFieldItemListInterface) {
            if ($items->needsSave()) {
              // For performance reasons we set the flag on the item, so that in
              // case of a big structure not to iterate down the whole structure
              // till the bottom each time we go one level deeper.
              $this->needsSave = TRUE;
              return TRUE;
            }
          }
        }
      }
    }
    elseif (!$this->needsSave) {
      // For performance reasons we set the flag on the item, so that in case of
      // a big structure not to iterate down the whole structure till the bottom
      // each time we go one level deeper.
      $this->needsSave = TRUE;
    }

    return $needs_save;
  }

}
