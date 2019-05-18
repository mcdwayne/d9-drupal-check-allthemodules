<?php

namespace Drupal\mfd\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin base of the generic field type.
 */
abstract class FieldNullBase extends FieldItemBase {
  use FieldNullTrait {
    fieldSettingsForm as getFieldSettingsFormBase;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

}