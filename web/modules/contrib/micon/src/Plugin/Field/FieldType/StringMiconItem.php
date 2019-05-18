<?php

namespace Drupal\micon\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\StringItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'string_micon' field type.
 *
 * @FieldType(
 *   id = "string_micon",
 *   label = @Translation("Icon"),
 *   description = @Translation("A field containing an icon."),
 *   default_widget = "string_micon",
 *   default_formatter = "string_micon"
 * )
 */
class StringMiconItem extends StringItem {

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    if ($value) {
      $value = \Drupal::service('micon.icon.manager')->getIconMatch($value);
    }
    return $value === NULL || $value === '';
  }

}
