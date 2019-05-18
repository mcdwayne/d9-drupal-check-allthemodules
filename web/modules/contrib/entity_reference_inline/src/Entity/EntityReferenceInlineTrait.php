<?php

namespace Drupal\entity_reference_inline\Entity;

/**
 * Optional trait for inline referenced entities.
 *
 * This trait isn't necessary. It only introduces the properties that are being
 * set by the module to avoid the usage of the magic set, get, isset and unset.
 */
trait EntityReferenceInlineTrait {

  /**
   * A flag indicating if the referenced entity has to be directly saved.
   *
   * If set the entity will be directly saved during field pre-save without
   * checking for translation changes.
   *
   * @see \Drupal\entity_reference_inline\Plugin\Field\FieldType\EntityReferenceInlineItem::preSave
   *
   * @var bool
   */
  public $needsSave;

  /**
   * The langcode of the entity translation which has been edited inline.
   *
   * @var string
   */
  public $inlineEditedLangcode;

  /**
   * A flag indicating if the entity has been loaded unchanged.
   *
   * @var
   */
  public $loadedUnchanged;

  /**
   * A flag indicating if the entity has been flagged with translation changes.
   *
   * @var
   */
  public $inlineEditedHasTranslationChanges;

  /**
   * A flag indicating if the entity is currently in an inline saving process.
   *
   * @var
   */
  public $inlineSaving;

  /**
   * A flag indicating that the entity has been inline saved.
   *
   * @var bool
   */
  public $inlineSaved;

  /**
   * Holds the revision ID of the entity built by the widget.
   *
   * After the entity is saved this property is the only way of retrieving the
   * revision ID the entity has been loaded and edited in.
   *
   * @var int
   */
  public $widgetLoadedRevisionID;

  /**
   * {@inheritdoc}
   */
  public function __clone() {
    if (!$this->translationInitialize) {
      // Ensure that the properties are actually cloned by overwriting the
      // original references with ones pointing to copies of them.
      $needs_save = $this->needsSave;
      $this->needsSave = &$needs_save;

      $inline_edited_langcode = $this->inlineEditedLangcode;
      $this->inlineEditedLangcode = &$inline_edited_langcode;

      $loaded_unchanged = $this->loadedUnchanged;
      $this->loadedUnchanged = &$loaded_unchanged;

      $inline_edited_has_translation_changes = $this->inlineEditedHasTranslationChanges;
      $this->inlineEditedHasTranslationChanges = &$inline_edited_has_translation_changes;

      $inline_saving = $this->inlineSaving;
      $this->inlineSaving = &$inline_saving;

      $inline_saved = $this->inlineSaved;
      $this->inlineSaved = &$inline_saved;

      $widget_loaded_revision_id = $this->widgetLoadedRevisionID;
      $this->widgetLoadedRevisionID = &$widget_loaded_revision_id;
    }
    parent::__clone();
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeTranslation($langcode) {
    $translation = parent::initializeTranslation($langcode);

    // Ensure that changes to fields, values and translations are propagated
    // to all the translation objects.
    $translation->needsSave = &$this->needsSave;
    $translation->inlineEditedLangcode = &$this->inlineEditedLangcode;
    $translation->loadedUnchanged = &$this->loadedUnchanged;
    $translation->inlineEditedHasTranslationChanges = &$this->inlineEditedHasTranslationChanges;
    $translation->inlineSaving = &$this->inlineSaving;
    $translation->inlineSaved = &$this->inlineSaved;
    $translation->widgetLoadedRevisionID = &$this->widgetLoadedRevisionID;

    return $translation;
  }

}
