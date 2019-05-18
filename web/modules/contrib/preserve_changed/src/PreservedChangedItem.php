<?php

namespace Drupal\preserve_changed;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\ChangedItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Provides a 'changed' field type class that knows to preserve the timestamp.
 */
class PreservedChangedItem extends ChangedItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);
    $properties['preserve'] = DataDefinition::create('boolean')
      ->setLabel(new TranslatableMarkup('Determines if the changed timestamp should be preserved.'));
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    if ($this->preserve) {
      return;
    }

    // Not calling parent::preSave() as that uses the deprecated REQUEST_TIME
    // which is immutable during the tests making it hard to be tested. From
    // this point forward, we're just copying the content of parent::preSave()
    // and we're replacing REQUEST_TIME with \Drupal::time()->getRequestTime().
    if (!$this->value) {
      $this->value = \Drupal::time()->getRequestTime();
    }
    else {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $this->getEntity();
      /** @var \Drupal\Core\Entity\ContentEntityInterface $original */
      $original = $entity->original;
      $langcode = $entity->language()->getId();
      if (!$entity->isNew() && $original->hasTranslation($langcode)) {
        $original_value = $original->getTranslation($langcode)->get($this->getFieldDefinition()->getName())->value;
        if ($this->value == $original_value && $entity->hasTranslationChanges()) {
          $this->value = \Drupal::time()->getRequestTime();
        }
      }
    }
  }

}
