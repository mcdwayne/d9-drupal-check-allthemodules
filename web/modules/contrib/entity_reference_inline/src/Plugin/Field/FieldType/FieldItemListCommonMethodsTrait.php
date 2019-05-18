<?php

namespace Drupal\entity_reference_inline\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a trait for common methods defined in the field item list.
 *
 * The methods are provided through a trait in order for them to be reusable.
 */
trait FieldItemListCommonMethodsTrait {

  /**
   * Whether to skip the pre-save method.
   *
   * @var bool
   */
  public $skipPreSave;

  /**
   * Whether to skip the check of ::hasTranslationChanges.
   *
   * @var bool
   */
  public $skipHasTranslationChangesCheck;

  /**
   * The result to return if skipping the ::hasTranslationChanges check.
   *
   * @var bool[]
   */
  public $hasTranslationChangesResult;

  /**
   * Whether to enforce saving the entity.
   *
   * @internal
   *
   * @var bool
   */
  public $needsSave;

  /**
   * {@inheritdoc}
   */
  public function referencedEntities() {
    if (empty($this->list)) {
      return [];
    }

    $target_entities = [];
    foreach ($this->list as $delta => $item) {
      $target_entities[$delta] = $item->entity;
    }

    return $target_entities;
  }

  /**
   * {@inheritdoc}
   *
   * We do not provide a default value for inline representation.
   */
  public function applyDefaultValue($notify = TRUE) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesForm(array &$form, FormStateInterface $form_state) {
    return ['#markup' => $this->t('No default value currently supported for inline references for: %type.', ['%type' => $this->getFieldDefinition()->getType()])];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    // Overwrite to not save the entities set on the field!
    FieldItemList::defaultValuesFormSubmit($element, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function needsSave() {
    if (!$this->needsSave) {
      /** @var  \Drupal\entity_reference_inline\Plugin\Field\FieldType\EntityReferenceInlineItemInterface $item */
      foreach ($this->list as $item) {
        if ($item->needsSave()) {
          $this->needsSave = TRUE;
          break;
        }
      }
    }
    return $this->needsSave;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->skipPreSave) {
      return;
    }
    parent::preSave();
  }

  /**
   * {@inheritdoc}
   */
  public function postSave($update) {
    $this->skipPreSave = NULL;
    $this->skipHasTranslationChangesCheck = NULL;
    $this->needsSave = NULL;
    return parent::postSave($update);
  }

  /**
   * {@inheritdoc}
   */
  public function hasTranslationChanges(FieldItemListInterface $list_to_compare, $langcode) {
    if ($this->skipHasTranslationChangesCheck) {
      return isset($this->hasTranslationChangesResult[$langcode]) ? $this->hasTranslationChangesResult[$langcode] : FALSE;
    }

    $parent = $this->getEntity();

    $inline_saving = isset($parent->inlineSaving) && $parent->inlineSaving;

    // Check for a list order change.
    foreach ($this as $delta => $item) {
      // If the current list do reference an entity at delta in the current
      // translation language, but the other list does not have an entity at
      // this delta then there is a translation change.
      if (!isset($list_to_compare[$delta])) {
        if ($item->entity->hasTranslation($langcode)) {
          return TRUE;
        }
      }
      // If both lists reference different entities at delta and one or both
      // of the referenced entities has the current translation language
      // then there is a translation change.
      // @todo this does not cover the case of putting an entity not having
      // the current translation between the both referenced entities - in
      // such a case without any real translation changes we would return
      // FALSE instead of TRUE.
      elseif ($item->target_id != $list_to_compare[$delta]->target_id) {
        if ($item->entity->hasTranslation($langcode) || $list_to_compare[$delta]->entity->hasTranslation($langcode)) {
          return TRUE;
        }
      }
    }

    // If the other list has more entities than the current one, continue
    // from the last delta of the current list and check if one the remaining
    // entities has a translation in the current language and if so then
    // there is a translation change.
    $delta = isset($delta) ? $delta + 1 : 0;
    for (; isset($list_to_compare[$delta]); $delta++) {
      if ($list_to_compare[$delta]->entity->hasTranslation($langcode)) {
        return TRUE;
      }
    }

    // The order of references entities relevant for the given language is
    // still identical. Check for translation changes within the referenced
    // entities.
    /** @var \Drupal\entity_reference_inline\Plugin\Field\FieldType\EntityReferenceInlineItemInterface $item */
    foreach ($this as $delta => $item) {
      // Check if the referenced revision ids are different.
      if (isset($list_to_compare[$delta]) && ($item->target_id == $list_to_compare[$delta]->target_id)) {
        /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
        $entity = $item->entity;
        $entity_to_compare_with = $list_to_compare[$delta]->entity;
        // We do not assume translation changes if the revision id has just
        // changed, but if the entities we are comparing have translation
        // changes between them.
        // If one the referenced entities does not have the translation
        // language then there is a translation change.
        // @todo is this condition correct or should we directly check for
        // translation changes in the else branch.
        $entity_has_translation = $entity->hasTranslation($langcode);
        $entity_to_compare_with_has_translation = $entity_to_compare_with->hasTranslation($langcode);
        if ($entity_has_translation XOR $entity_to_compare_with_has_translation) {
          return TRUE;
        }
        // In the case that both the referenced entities does not have the
        // current translation language code then skip them from checking.
        elseif (!$entity_has_translation && !$entity_to_compare_with_has_translation) {
          continue;
        }
        // Otherwise perform a deeper check between both entity revisions.
        else {
          // ::hasTranslationChanges compares the entity with the unchanged
          // entity, but as first it checks if original is already set and
          // if so it uses it instead of loading the unchanged entity from
          // the storage. We use this feature to compare both entity
          // revisions.

          // If inline saving is running we set as original the already loaded
          // unchanged entity from $list_to_compare and do not unset it again,
          // as if we are going to save the entity then it is not needed
          // anymore.
          if ($inline_saving && isset($entity->original)) {
            $original = $entity->original;
          }

          $entity->original = $entity_to_compare_with;
          $has_translation_changes = $entity->getTranslation($langcode)->hasTranslationChanges();

          if ($inline_saving && isset($original)) {
            $entity->original = $original;
          }

          // If the entity has translation changes in comparison with the
          // other revision then there is a translation change.
          if ($has_translation_changes) {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

}
