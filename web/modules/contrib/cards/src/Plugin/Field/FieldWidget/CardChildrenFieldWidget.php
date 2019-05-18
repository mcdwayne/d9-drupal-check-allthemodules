<?php

namespace Drupal\cards\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entityreference_view_mode\Plugin\Field\FieldWidget\EntityReferenceViewModeFieldWidget;


/**
 * Plugin implementation of the 'field_example_text' widget.
 *
 * @FieldWidget(
 *   id = "card_children_field_widget",
 *   module = "cards",
 *   label = @Translation("Card ChildrenWidget"),
 *   field_types = {
 *     "card_children_field_type"
 *   }
 * )
 */
class CardChildrenFieldWidget extends CardFieldWidget {

  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {

    // Call the parent classes formElement function.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // The content field is not necessary for the child widget so we just hide it.
    $element['content']['#access'] = FALSE;
    return $element;
  }
}
