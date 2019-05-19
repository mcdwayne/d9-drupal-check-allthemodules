<?php

namespace Drupal\tripadvisor_integration\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextfieldWidget;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the plain text widget.
 *
 * @FieldWidget(
 *   id = "tripadvisor_integration_text",
 *   label = @Translation("TripAdvisor ID Default"),
 *   field_types = {
 *     "tripadvisor_integration_tripadvisor_id"
 *   }
 * )
 */
class TripAdvisorWidget extends StringTextfieldWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = $element + parent::formElement($items, $delta, $element, $form, $form_state);
    $element['value']['#size'] = 32;
    return $element;
  }

}
