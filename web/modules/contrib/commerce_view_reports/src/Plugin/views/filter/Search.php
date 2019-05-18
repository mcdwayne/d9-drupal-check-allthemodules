<?php

namespace Drupal\commerce_reports\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\Date;
use Drupal\Core\Form\FormStateInterface;


/**
 * Custom Filter to handle end dates stored as a timestamp.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search")
 */
class Search extends Date {

   /**l
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
if ($this->operator === 'Custom Date') {
        $form['search']['min'] = [
        '#type' => 'date',
        '#title' => $this->t('Start Date'),
        '#default_value' => $this->options['search']['min'],
      ];
        $form['search']['max'] = [
        '#type' => 'date',
        '#title' => $this->t('End Date'),
        '#default_value' => $this->options['search']['max'],
      ];
      }
  }
public function operators() {
    $operators = parent::operators();
    $operators += [

      'today' => [
        'title' => $this->t('Today'),
        'short' => $this->t('today'),
        'method' => 'today',
        'values' => 0,
      ],

      'yesterday' => [
        'title' => $this->t('Yesterday'),
        'short' => $this->t('yest'),
        'method' => 'op_day_of_week',
        'values' => 0,
      ],

      'Last 30 days' => [
        'title' => $this->t('Last 30 days'),
        'short' => $this->t('month'),
        'method' => 'op_day_of_month',
        'values' => 0,
      ],

      'Custom Date' => [
        'title' => $this->t('Custom Date'),
        'short' => $this->t('Date'),
        'method' => 'custom_date',
        'values' => 2,
      ],
    ];
    return $operators;
  }


  /**
   * {@inheritdoc}
   */
    protected function custom_date($field) {

    //if ($this->operator === 'between') {
      $min = str_replace('/', '-', $this->value['min']);
      $max = str_replace('/', '-', $this->value['max']);
      $this->value['min'] = strtotime($min);
    // If End date is current date
    if (strtotime($max) == strtotime(date('Y-m-d'))) {
      $this->value['max'] = strtotime('now');
    }
    // If End date is a past date
    if (strtotime($max) < strtotime(date('Y-m-d'))) {
    // Increment the date by one
       $this->value['max'] = strtotime('+1 day', strtotime($max));
    }
    // If End Date is a future date
    if (strtotime($max) > strtotime(date('Y-m-d'))) {
     $this->value['max'] = strtotime($max);
    }
     $this->query->addWhere($this->options['group'], $field, [$this->value['min'], $this->value['max']], 'BETWEEN');
   // }
  }

   protected function op_day_of_week($field) {
    $current_time = strtotime(date('d-m-Y'));
    $yesterday = strtotime('-1 day', strtotime(date('d-m-Y')));

     $this->query->addWhere($this->options['group'], $field, [$yesterday, $current_time ], 'BETWEEN');
    }
   protected function op_day_of_month($field) {
    $current_time = strtotime(now);
    $last_month = strtotime('-30 day', strtotime(date('d-m-Y')));

    $this->query->addWhere($this->options['group'], $field, [ $last_month, $current_time ], 'BETWEEN');
    }

  protected function today($field)  {
    $today = strtotime(date('d-m-Y'));
    $current_time = strtotime(now);
    $this->query->addWhere($this->options['group'], $field, [ $today, $current_time ], 'BETWEEN');
  }


};