<?php

namespace Drupal\weeklytime\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\weeklytime\Plugin\Field\FieldType\WeeklyTimeField;

/**
 * Filter to handle dates stored as a weekly time field.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("weeklytime")
 */
class WeeklyTimeFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#options' => WeeklyTimeField::timeLabels(),
      '#default_value' => is_string($this->value) ? $this->value : [],
      '#multiple' => $this->options['expose']['multiple'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $field_name = $this->ensureMyTable() . '.field_time_time';

    $conditions = db_or();

    $options = WeeklyTimeField::timeOptions();
    $values = is_array($this->value) ? $this->value : [$this->value];
    foreach ($values as $value) {
      $selected_value = $value;
      if ($selected_value === WeeklyTimeField::DEFAULT_TIME) {
        $value = WeeklyTimeField::defaultTime();
        if ($value === NULL) {
          return;
        }
      }

      foreach ($options[$value]['ranges'] as $range) {
        $start = WeeklyTimeField::stringToTime($range[0]);
        if ($selected_value === WeeklyTimeField::DEFAULT_TIME) {
          // When finding the next meeting, make sure that the start time is
          // mostly (allow 30 minutes buffer) in the future,.
          $now = WeeklyTimeField::stringToTime(date('Hi'));
          if ($now - 30 > $start) {
            $start = $now - 30;
          }
        }
        $end = WeeklyTimeField::stringToTime($range[1]);
        $conditions->where("$field_name >= $start AND $field_name < $end");
      }
    }

    $this->view->query->addWhere($this->options['group'], $conditions);
  }

}
