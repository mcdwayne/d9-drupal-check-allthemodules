<?php

namespace Drupal\dpl\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'dpl__url' widget.
 *
 * @FieldWidget(
 *   id = "dpl__url",
 *   label = @Translation("Preview URL"),
 *   field_types = {
 *     "string",
 *     "string",
 *   },
 * )
 */
class PreviewUrlWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    return $element;
  }

}
