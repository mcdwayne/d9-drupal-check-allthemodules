<?php

namespace Drupal\date_recur_interactive\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\Html;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\date_recur\Plugin\Field\FieldWidget\DateRecurBasicWidget;

/**
 * Plugin implementation of the 'date_recur_interactive_widget' widget.
 *
 * @FieldWidget(
 *   id = "date_recur_interactive_widget",
 *   label = @Translation("Date recur interactive widget"),
 *   field_types = {
 *     "date_recur"
 *   }
 * )
 */
class DateRecurInteractiveWidget extends DateRecurBasicWidget {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $id = Html::getUniqueId('date-recur');
    $element['value']['#attributes']['data-date-recur-start'] = $id;
    $element['rrule']['#attributes']['data-date-recur-rrule'] = $id;
    $element['#attached']['library'][] = 'date_recur_interactive/widget';
    $element['first_occurrence']['#weight'] = -10;
    return $element;
  }

}
