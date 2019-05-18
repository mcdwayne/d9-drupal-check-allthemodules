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
 * @ViewsFilter("product")
 */
class Product extends Date {

   /**l
   * {@inheritdoc}
   */
  public function buildExposedForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);
if ($this->operator === 'Custom Date') {
        $form['product']['min'] = [
        '#type' => 'date',
        '#title' => $this->t('Start Date'),
        '#default_value' => $this->options['product']['min'],
      ];
        $form['product']['max'] = [
        '#type' => 'date',
        '#title' => $this->t('End Date'),
        '#default_value' => $this->options['product']['max'],
      ];
      }
  }
public function operators() {
    $operators = parent::operators();
    $operators += [

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

};