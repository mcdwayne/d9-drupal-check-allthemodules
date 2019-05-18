<?php

/**
 * @file
 * Contains \Drupal\machine_name_widget\Plugin\Field\FieldWidget\MachineNameWidget.
 */

namespace Drupal\machine_name_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'machine_name' widget.
 *
 * @FieldWidget(
 *   id = "machine_name",
 *   label = @Translation("Machine Name field"),
 *   field_types = {
 *     "string"
 *   }
 * )
 */
class MachineNameWidget extends StringTextfieldWidget {
  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    foreach (self::getSettingsKeys() as $key) {
      $settings[$key] = '';
    }

    return $settings;
  }


  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#type'] = 'machine_name';

    foreach ($this->getSettingsKeys() as $key) {
      if ($setting = $this->getSetting($key)) {
        $element['value']['#machine_name'][$key] = $setting;
      }
    }

    // If a label is not provided, use the field label.
    if (empty($element['value']['#machine_name']['label'])) {
      if ($label = $items->getFieldDefinition()->getLabel()) {
        $element['value']['#machine_name']['label'] = (string) $label;
      }
    }

    return $element;
  }

  /**
   * Gets the machine name settings.
   *
   * @return array
   *   Array of keys.
   */
  protected static function getSettingsKeys() {
    return [
      'exists',
      'source',
      'label',
      'replace_pattern',
      'replace',
    ];
  }

}
