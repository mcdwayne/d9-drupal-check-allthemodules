<?php

namespace Drupal\ext_redirect\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'source_path_widget' widget.
 *
 * @FieldWidget(
 *   id = "source_path_widget",
 *   label = @Translation("Source Path Widget"),
 *   field_types = {
 *     "source_path"
 *   }
 * )
 */
class SourcePathWidget extends WidgetBase {
  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['source_path'] = $element + [
      '#type' => 'textarea',
      '#default_value' => isset($items[$delta]->source_path) ? $items[$delta]->source_path : NULL,
      '#resizable' => 'both',
    ];

    return $element;
  }

}
