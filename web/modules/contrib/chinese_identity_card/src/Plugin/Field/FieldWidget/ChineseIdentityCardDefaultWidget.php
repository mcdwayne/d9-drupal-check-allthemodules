<?php

namespace Drupal\chinese_identity_card\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @FieldWidget(
 *   id = "chinese_identity_card_default",
 *   module = "chinese_identity_card",
 *   label = @Translation("Chinese identity card default widget"),
 *   field_types = {
 *      "chinese_identity_card"
 *   }
 * )
 */
class ChineseIdentityCardDefaultWidget extends WidgetBase {
  /**
   * @inheritdoc
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element += [
      '#type' => 'textfield',
      '#title' => 'card',
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
      '#element_validate' => [
        [$this, 'validate'],
      ],
    ];

    return [
      'value' => $element,
    ];
  }

  /**
   * Validate value with custom validation.
   *
   * @param                                      $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function validate($element, FormStateInterface $form_state) {
    $value = $element['#value'];
    if (strlen($value) == 0) {
      $form_state->setValueForElement($element, '');

      return;
    }

    $validator_id = $this->getFieldSetting('validator_id');

    /** @var \Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidatorManager $chinese_identity_card_validator */
    $chinese_identity_card_validator = \Drupal::service('plugin.manager.chinese_identity_card_validator');

    $validator = $chinese_identity_card_validator->createInstance($validator_id, []);

    if (!$validator->validate($value)) {
      $form_state->setError($element, t("Not a valid id number."));
    }
  }
}