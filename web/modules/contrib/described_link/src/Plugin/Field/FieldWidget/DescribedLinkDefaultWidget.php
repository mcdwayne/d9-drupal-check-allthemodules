<?php

namespace Drupal\described_link\Plugin\Field\FieldWidget;

use Drupal\link\Plugin\Field\FieldWidget\LinkWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'described_link' widget.
 *
 * @FieldWidget(
 *   id = "described_link_default",
 *   label = @Translation("Described Link"),
 *   field_types = {
 *     "described_link"
 *   }
 * )
 */
class DescribedLinkDefaultWidget extends LinkWidget {
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $widget = parent::formElement($items, $delta, $element, $form, $form_state);

    $widget['description'] = [
      '#title' => $this->t('Description'),
      '#type' => 'textfield',
      '#default_value' => isset($items[$delta]) ? $items[$delta]->description : $default,
    ];

    return $widget;
  }
}