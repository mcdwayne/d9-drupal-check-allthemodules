<?php

namespace Drupal\field_union\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class for a field union widget.
 *
 * @FieldWidget(
 *   id = "field_union",
 *   label = @Translation("Field Union"),
 *   field_types = {
 *     "field_union"
 *   }
 * )
 */
class FieldUnionWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // @todo
    return $element;
  }

}
