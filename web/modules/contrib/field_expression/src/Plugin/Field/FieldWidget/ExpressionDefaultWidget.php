<?php

namespace Drupal\field_expression\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'field_expression_widget' widget.
 *
 * @FieldWidget(
 *   id = "field_expression_default",
 *   label = @Translation("Field Token Expression"),
 *   field_types = {
 *     "field_expression"
 *   }
 * )
 */
class ExpressionDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    // Need to set a value so that the preSave triggers on the field item
    // definition.
    $element['value'] = [
      '#type' => 'value',
      '#value' => $this->getFieldSetting('expression'),
    ];

    return $element;
  }

}
