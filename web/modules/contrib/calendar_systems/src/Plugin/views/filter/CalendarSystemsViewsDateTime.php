<?php

namespace Drupal\calendar_systems\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\datetime\Plugin\views\filter\Date;
use Drupal\calendar_systems\Plugin\views\argument\CalendarSystemsArgHandlerTrait;

/**
 * Filter to handle dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("date")
 */
class CalendarSystemsViewsDateTime extends Date {

  /**
   * @inheritDoc
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    if (isset($form['value'])) {
      $form['value']['#attributes']['autocomplete'] = 'off';
    }
  }

  /**
   * @inheritDoc
   */
  protected function opSimple($field) {
    $this->value['value'] = CalendarSystemsArgHandlerTrait::translate($this->value['value']);

    $cal = calendar_systems_factory();

    if ($cal) {
      if ($cal->parse($this->value['value'], 'Y-m-d H:i:s')) {
        $this->value['value'] = $cal->xFormat('Y-m-d H:i:s');
      }
      elseif ($cal->parse($this->value['value'], 'Y-m-d')) {
        $this->value['value'] = $cal->xFormat('Y-m-d');
      }
      else {
        $this->value['value'] = \Drupal::time()->getRequestTime();
      }
    }

    parent::opSimple($field);
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    $this->value['min'] = CalendarSystemsArgHandlerTrait::translate($this->value['min']);
    $this->value['max'] = CalendarSystemsArgHandlerTrait::translate($this->value['max']);

    $cal = calendar_systems_factory();

    if ($cal) {
      if ($cal->parse($this->value['min'], 'Y-m-d H:i:s')) {
        $this->value['min'] = $cal->xFormat('Y-m-d H:i:s');
      }
      elseif ($cal->parse($this->value['min'], 'Y-m-d')) {
        $this->value['min'] = $cal->xFormat('Y-m-d');
      }
      else {
        $this->value['min'] = \Drupal::time()->getRequestTime();
      }

      if ($cal->parse($this->value['max'], 'Y-m-d H:i:s')) {
        $this->value['max'] = $cal->xFormat('Y-m-d H:i:s');
      }
      elseif ($cal->parse($this->value['max'], 'Y-m-d')) {
        $this->value['max'] = $cal->xFormat('Y-m-d');
      }
      else {
        $this->value['max'] = \Drupal::time()->getRequestTime();
      }
    }

    parent::opBetween($field);
  }

}
