<?php

namespace Drupal\library\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'library_item_field_type' field type.
 *
 * @FieldType(
 *   id = "library_item_field_type",
 *   label = @Translation("Library item entry"),
 *   description = @Translation("Adds library item support to a node."),
 *   default_widget = "library_item_field_widget",
 *   default_formatter = "library_item_field_formatter",
 * )
 */
class LibraryItemFieldType extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    $settings = parent::defaultFieldSettings();
    $settings['due_date'] = 30;
    $settings['barcode_generation'] = TRUE;
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $elements['due_date'] = [
      '#title' => t('Due date'),
      '#type' => 'number',
      '#required' => TRUE,
      '#min' => 0,
      '#default_value' => $this->getSetting('due_date'),
    ];
    $elements['barcode_generation'] = [
      '#title' => t('Automatically generate barcodes'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('barcode_generation'),
    ];
    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $overridden['target_type'] = [
      '#type' => 'value',
      '#value' => 'library_item',
    ];
    return $overridden;
  }

}
