<?php

namespace Drupal\field_expression\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_expression_value_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "field_expression_value",
 *   label = @Translation("Evaluated Expression"),
 *   field_types = {
 *     "field_expression"
 *   }
 * )
 */
class ExpressionValueFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'always_evaluate' => FALSE
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element['always_evaluate'] = [
      '#type' => 'checkbox',
      '#title' => t('Always Evaluate Expression?'),
      '#description' => t('Check this box to evaluate the expression every time it is rendered. Otherwise, it will only be evaluated with the entity it is attached to is saved. This setting can be useful if you have complex tokens that use referenced content that may change without this entity updating. Be careful that large expressions with lots of tokens could cause performance issues if this setting is enabled, however.'),
      '#default_value' => $this->getSetting('always_evaluate'),
      '#empty_option' => t('- Select wrapper -'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary[] = $this->t('Always Evaluate Expression: @always_evaluate', ['@always_evaluate' => $this->getSetting('always_evaluate') ? 'Yes' : 'No']);
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    $value = '';

    // We're forcing single cardinality for this field type in a form alter
    // so we just need to check the first item, and then work with that if it's
    // not empty.
    if ($this->getSetting('always_evaluate')) {
      // If there's no value yet, we append an item so that we can grab
      if (empty($items[0])) {
        $items->appendItem();
      }
      $value = $items[0]->evaluateExpression($items[0]->getFieldDefinition()->getSetting('expression'));
    }
    elseif (!empty($items[0])) {
      $value = $items[0]->value;
    }

    $element = [
      '#markup' => $value,
    ];

    return $element;
  }

}
