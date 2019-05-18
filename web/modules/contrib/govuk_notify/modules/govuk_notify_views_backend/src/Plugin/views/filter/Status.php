<?php

namespace Drupal\govuk_notify_views_backend\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple filter to handle filtering GovUK messages by status.
 *
 * @ViewsFilter("govuk_notify_filter_status")
 */
class Status extends FilterPluginBase {

  public $noOperator = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {

    $allowed_values = [
      'sending',
      'delivered',
      'failed',
      'permanent-failure',
      'temporary-failure',
      'technical-failure',
    ];

    $options = ['0' => '-- select one --'];

    foreach ($allowed_values as $status) {
      $options[$status] = $status;
    }

    $form['value'] = [
      '#type' => 'select',
      '#title' => t('Value'),
      '#options' => $options,
      '#default_value' => $this->value,
      '#description' => t('Filter messages by their status'),
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
