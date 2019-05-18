<?php

namespace Drupal\headline_group\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'headline_complete' widget.
 *
 * @FieldWidget(
 *   id = "headline_complete",
 *   label = "Headline Group (all fields)",
 *   field_types = {
 *     "headline_group"
 *   }
 * )
 */
class HeadlineCompleteWidget extends BaseHeadlineWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    if ($this->supportsSuperhead()) {
      $element['superhead'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Superhead'),
        '#placeholder' => NULL,
        '#default_value' => isset($items[$delta]->superhead) ? $items[$delta]->superhead : NULL,
        '#maxlength' => 255,
      ];
    }

    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    if ($this->supportsSubhead()) {
      $element['subhead'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Subhead'),
        '#placeholder' => NULL,
        '#default_value' => isset($items[$delta]->subhead) ? $items[$delta]->subhead : NULL,
        '#maxlength' => 255,
      ];
    }

    $element += [
      '#type' => 'fieldset',
    ];

    return $element;

  }
}
