<?php

namespace Drupal\mfd\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;

/**
 * Common methods for the FieldNullTrait plugins.
 *
 * The FieldType plugins in this module descend from either FieldItemBase
 * (numbers via ComputedFieldItemBase) or StringItemBase (strings via
 * ComputedStringItemBase). As they have no common ancestry outside of Core,
 * it's necessary to introduce this trait to prevent code duplication across
 * hierarchies.
 */
trait FieldNullTrait {

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->setValue(NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];

    return $element;
  }

}