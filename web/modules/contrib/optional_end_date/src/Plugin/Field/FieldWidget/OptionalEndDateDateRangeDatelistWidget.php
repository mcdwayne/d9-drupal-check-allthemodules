<?php

namespace Drupal\optional_end_date\Plugin\Field\FieldWidget;

use Drupal\datetime_range\Plugin\Field\FieldWidget\DateRangeDatelistWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Override the datelist widget.
 */
class OptionalEndDateDateRangeDatelistWidget extends DateRangeDatelistWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $optional_end_date = $this->getFieldSetting('optional_end_date');

    $element['end_value']['#title'] = $optional_end_date ? $this->t('End date (optional)') : $this->t('End date');
    if ($element['#required'] && $optional_end_date) {
      $element['end_value']['#required'] = FALSE;
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateStartEnd(array &$element, FormStateInterface $form_state, array &$complete_form) {
    $end_date = $element['end_value']['#value']['object'];

    if (!$this->getFieldSetting('optional_end_date') && $end_date === NULL) {
      $form_state->setError($element['end_value'], $this->t('The @title end date is required', ['@title' => $element['#title']]));
    }

    parent::validateStartEnd($element, $form_state, $complete_form);
  }

}
