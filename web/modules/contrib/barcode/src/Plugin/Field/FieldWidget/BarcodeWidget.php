<?php

namespace Drupal\barcode\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the 'barcode' field widget.
 *
 * @FieldWidget(
 *   id = "barcode",
 *   label = @Translation("Barcode"),
 *   field_types = {
       "barcode",
       "barcode_matrix"
     },
 * )
 */
class BarcodeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'default' => '',
      'custom_placeholder' => '',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $settings = $this->getSettings();
    $types = $this->getFieldSetting('types');

    $element['default'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Barcode Type'),
      '#required' => TRUE,
      '#options' => $types,
      '#default_value' => ($settings['default']) ? $settings['default'] : @array_pop(array_keys($types)),
    ];
    $element['custom_placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder'),
      '#default_value' => $settings['custom_placeholder'],
      '#description' => $this->t('Text that will be shown inside the field until a value is entered. Leave blank to use the placeholder for the barcode type.'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings = $this->getSettings();
    $types = $this->getFieldSetting('types');
    drupal_set_message(var_export($types,TRUE));
    $summary[] = $this->t('Default Barcode type: @type', [
      '@type' => ($settings['default']) ? $types[$settings['default']] : array_pop($types),
    ]);

    if ($settings['custom_placeholder']) {
      $summary[] = $this->t('Placeholder: @placeholder', ['@placeholder' => $settings['placeholder']]);
    }
    else {
      $summary[] = $this->t('Placeholder: Barcode type\'s default');
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\barcode\Plugin\Field\FieldType\BarcodeItemBase $item */
    $item = $items[$delta];
    $type = isset($item->type) ? $item->type : $this->getSetting('default');
    $types = $this->getFieldSetting('types');

    $element['value'] = [
      '#title' => $this->t('Barcode'),
      '#type' => 'textfield',
      //'#maxlength' => $item->
      '#default_value' => isset($item->value) ? $item->value : NULL,
      '#placeholder' => $this->getPlaceholder($type, $item),
      '#description' => $item->types[$type]['description'],
    ];

    $element['type'] = [
      '#title' => $this->t('Barcode Type'),
      '#type' => 'select',
      '#options' => $types,
      '#default_value' => $type,
      '#access' => count($types) != 1,
    ];

    return $element;
  }

  /**
   * Get the placeholder for the current type of barcode or custom placeholder.
   *
   * @param string $type
   *   The current type.
   */
  public function getPlaceholder($type, $item) {
    if (!$placeholder = $this->getSettings('custom_placeholder')) {
      $placeholder = $item->types[$type]['placeholder'];
    }
    return $placeholder;
  }
}
