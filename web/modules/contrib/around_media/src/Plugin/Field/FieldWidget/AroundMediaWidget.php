<?php

namespace Drupal\around_media\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'around_media' widget.
 *
 * @FieldWidget(
 *   id = "around_media",
 *   label = @Translation("Around Media"),
 *   field_types = {
 *     "around_media"
 *   }
 * )
 */
class AroundMediaWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['tour'] = $element + [
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]->tour) ? $items[$delta]->tour : NULL,
      '#size' => 30,
      '#maxlength' => 255,
    ];

    return $element;
  }

}
