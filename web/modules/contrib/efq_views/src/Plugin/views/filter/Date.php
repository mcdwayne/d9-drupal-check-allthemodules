<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\Date.
 */

namespace Drupal\efq_views\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\Date as ViewsDate;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler for date properties.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_date")
 */
class Date extends ViewsDate {

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    unset($form['value']['type']);
    $form['value']['value']['#description'] = t('A date in any machine readable format. CCYY-MM-DD HH:MM:SS is preferred.');
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $info = $this->operators();
    if (!empty($info[$this->operator]['method'])) {
      $this->{$info[$this->operator]['method']}($this->real_field);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($field) {
    if ($this->operator == 'between') {
      $a = strtotime($this->value['min']);
      $b = strtotime($this->value['max']);
    }
    else {
      $a = strtotime($this->value['max']);
      $b = strtotime($this->value['min']);
    }
    return array($a, $b);
  }

  /**
   * {@inheritdoc}
   */
  protected function  opSimple($field) {
    return strtotime($this->value['value']);
  }

}
