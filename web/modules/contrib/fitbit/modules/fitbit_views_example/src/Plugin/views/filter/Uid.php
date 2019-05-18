<?php

namespace Drupal\fitbit_views_example\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Simple filter to handle filtering Fitbit results by uid. Very basic filter
 * that only allows equals operator.
 *
 * @ViewsFilter("fitbit_uid")
 */
class Uid extends FilterPluginBase  {

  public $no_operator = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value'),
      '#size' => 30,
      '#default_value' => $this->value,
    ];
  }
}
