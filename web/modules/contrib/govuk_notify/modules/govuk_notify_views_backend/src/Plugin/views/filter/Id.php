<?php

namespace Drupal\govuk_notify_views_backend\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple filter to handle filtering GovUK messages by id.
 *
 * In Gov.UK talk an id is called a 'reference'.
 *
 * @ViewsFilter("govuk_notify_filter_id")
 */
class Id extends FilterPluginBase {

  public $noOperator = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    $form['value'] = [
      '#type' => 'textfield',
      '#title' => t('Value'),
      '#default_value' => $this->value,
      '#description' => t('Filter messages by their id'),
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function addWhere($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }
    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->setWhereGroup('AND', $group);
    }
    $this->where[$group]['conditions'][] = [
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    ];
  }

}
