<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\FieldInOperator.
 */

namespace Drupal\efq_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Handle matching of multiple options selectable via checkboxes
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_field_in_operator")
 */
class FieldInOperator extends InOperator {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['delta'] = array('default' => NULL);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['delta'] = array(
      '#type' => 'textfield',
      '#title' => t('Delta'),
      '#default_value' => $this->options['delta'],
      '#description' => t('Numeric delta group identifier. If provided, only values attached to the same delta are matched. Leave empty to match all values.'),
    );
  }

  /**
   * {@inheritdoc}
   */
  function opSimple() {
    if (empty($this->value)) {
      return;
    }
    // We use array_values() because the checkboxes keep keys and that can cause
    // array addition problems.
    $this->query->query->fieldCondition($this->definition['field_name'], $this->real_field, array_values($this->value), $this->operator, efq_views_extract_delta($this));
  }

}
