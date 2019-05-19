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
 * @ViewsFilter("weeklydays")
 */
class WeeklyDaysFilter extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#options' => WeeklyTimeField::dayLabels(),
      '#default_value' => is_array($this->value) ? $this->value : [],
      '#multiple' => $this->options['expose']['multiple'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();

    $conditions = db_or();

    $values = is_array($this->value) ? $this->value : [$this->value];
    foreach ($values as $day) {
      if ($day == WeeklyTimeField::DEFAULT_DAY) {
        $day = WeeklyTimeField::defaultDay();
      }

      $value = ($this->operator == '=') ? '1' : '0';
      $conditions->condition("{$table}.field_time_{$day}", $value, $this->operator);
    }

    $this->query->addWhere($this->options['group'], $conditions);
  }

}
