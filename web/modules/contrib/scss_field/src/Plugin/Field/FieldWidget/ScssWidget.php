<?php

namespace Drupal\scss_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Plugin\Field\FieldWidget\StringTextareaWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * A widget for SCSS fields.
 *
 * @FieldWidget(
 *   id = "scss",
 *   label = @Translation("SCSS widget"),
 *   field_types = {
 *     "scss"
 *   }
 * )
 */
class ScssWidget extends StringTextareaWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $main_widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $element = $main_widget['value'];
    $element['#type'] = 'text_format';
    $element['#format'] = 'scss';
    $element['#base_type'] = $main_widget['value']['#type'];
    return $element;
  }

}
