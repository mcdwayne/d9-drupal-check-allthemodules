<?php

namespace Drupal\unique_code_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'unique_code_widget' widget.
 *
 * @FieldWidget(
 *   id = "unique_code_widget",
 *   label = @Translation("Unique code widget"),
 *   field_types = {
 *     "unique_code"
 *   }
 * )
 */
class UniqueCodeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'size' => 60,
      'placeholder' => 'The code generation is automated!',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];

    $elements['size'] = [
      '#type' => 'number',
      '#title' => t('Size of textfield'),
      '#default_value' => $this->getSetting('size'),
      '#required' => TRUE,
      '#min' => 1,
    ];

    $elements['placeholder'] = [
      '#type' => 'textfield',
      '#title' => t('Placeholder'),
      '#default_value' => $this->getSetting('placeholder'),
      '#description' => t('Text that will be shown inside the field until a value is entered. This hint is usually a sample value or a brief description of the expected format.'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $summary[] = t('Textfield size: @size', ['@size' => $this->getSetting('size')]);
    if (!empty($this->getSetting('placeholder'))) {
      $summary[] = t('Placeholder: @placeholder', ['@placeholder' => $this->getSetting('placeholder')]);
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $code_str = '';
    // $item is where the current saved values are stored.
    $item =& $items[$delta];
    $item_definition = $item->getFieldDefinition();
    $item_name = $item->getFieldDefinition()->getName();
    $item_parent = $item->getParent()->getEntity()->getEntityType()->id();
    if ($item->isEmpty()) {
      // Perform the generation until the code is unique.
      do {
        // Generate a new code only if it doesn't exist yet.
        $tmp_code = $item->generateSampleValue($item_definition);
        $unique = $item->isUnique($tmp_code, $item_parent, $item_name);
        $code_str = $tmp_code;
      } while (FALSE === $unique);
    }
    else {
      // Get the stored value for this field.
      $code_str = $item->value;
    }
    $element['value'] = $element + [
      '#type' => 'textfield',
      '#default_value' => $code_str,
      '#size' => $this->getSetting('size'),
      '#placeholder' => $this->getSetting('placeholder'),
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#attributes' => ['readonly' => 'readonly'],
      '#element_validate' => ['unique_code_validate'],
    ];

    return $element;
  }

}
