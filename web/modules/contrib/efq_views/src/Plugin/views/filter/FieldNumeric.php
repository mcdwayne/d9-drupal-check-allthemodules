<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\FieldNumeric.
 */

namespace Drupal\efq_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;

/**
 * Numeric filter for fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_numeric")
 */
class FieldNumeric extends Numeric {

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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
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
  protected function opSimple($column) {
    $this->query->query->fieldCondition($this->definition['field_name'], $column, $this->value['value'], $this->operator, efq_views_extract_delta($this));
  }

  /**
   * {@inheritdoc}
   */
  protected function opBetween($column) {
    $this->query->query->fieldCondition($this->definition['field_name'], $column, $this->value['value'], "BETWEEN", efq_views_extract_delta($this));
  }

}
