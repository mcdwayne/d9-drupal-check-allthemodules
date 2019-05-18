<?php

namespace Drupal\cards\Plugin\Field\FieldWidget;


use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\entityreference_view_mode\Plugin\Field\FieldWidget\EntityReferenceViewModeFieldWidget;
use EntityReferenceViewModeFieldWidgetTrait;

/**
 * Plugin implementation of the 'field_example_text' widget.
 *
 * @FieldWidget(
 *   id = "card_field_widget",
 *   module = "cards",
 *   label = @Translation("Card Widget"),
 *   field_types = {
 *     "card_field_type"
 *   }
 * )
 */
class CardFieldWidget extends EntityReferenceViewModeFieldWidget {

  use CardWidgetTrait;

  /**
   * {@inheritdoc}
   */
  public function formElement(
    FieldItemListInterface $items,
    $delta,
    array $element,
    array &$form,
    FormStateInterface $form_state
  ) {

    // Call the parent classes formElement function.
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    $element = $this->cardOptions($items, $delta, $element, $form, $form_state);

    return $element;
  }

}
