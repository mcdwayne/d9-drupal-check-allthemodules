<?php

namespace Drupal\passcode_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_passcode_text' widget.
 *
 * @FieldWidget(
 *   id = "field_passcode_text",
 *   module = "passcode_field",
 *   label = @Translation("Text field with button"),
 *   field_types = {
 *     "field_passcode"
 *   }
 * )
 */
class PasscodeWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('No. of digits in Passcode: @size', ['@size' => $this->getSetting('digits_no')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'digits_no' => 6,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $keys = range(3, 10);
    $element = [];
    $element['digits_no'] = [
      '#type' => 'select',
      '#title' => $this->t('No. of Digits'),
      '#options' => array_combine($keys, $keys),
      '#default_value' => !empty($this->getSetting('digits_no')) ? $this->getSetting('digits_no') : 6,
      '#description' => $this->t('The total number of digits for the passcode to contain.'),
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['passcode'] = [
      '#type' => 'fieldset',
      '#title' => $element['#title'],
      '#tree' => TRUE,
    ];

    $element['passcode']['random_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Code'),
      '#default_value' => isset($items[$delta]->passcode) ? $items[$delta]->passcode : '',
      '#required' => $element['#required'],
      '#size' => 12,
      '#attributes' => [
        'maxlength' => 12,
        'class' => ['passcode_random_number'],
        'style' => 'text-transform: uppercase',
      ],
    ];

    $element['passcode']['generate_button'] = [
      '#type' => 'button',
      '#value' => 'Generate',
      '#attached' => [
        'library' => [
          'passcode_field/passcode_lib',
        ],
        'drupalSettings' => [
          'passcode_field' => [
            'digits' => $this->getSetting('digits_no'),
          ],
        ],
      ],
      '#attributes' => ['class' => ['passcode_generate_btn']],
    ];

    return ['value' => $element];

  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as $key => $value) {
      if (isset($value['value']['passcode']['random_number'])) {
        $values[$key] = ['passcode' => $value['value']['passcode']['random_number']];
      }
    }
    return $values;
  }

}
