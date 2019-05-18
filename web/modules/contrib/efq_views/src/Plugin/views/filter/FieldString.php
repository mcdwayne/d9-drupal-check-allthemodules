<?php

/**
 * @file
 * Contains \Drupal\efq_views\Plugin\views\filter\FieldString.
 */

namespace Drupal\efq_views\Plugin\views\filter;
use Drupal\Core\Form\FormStateInterface;


/**
 * String filter for fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("efq_field_string")
 */
class FieldString extends String {

  /**
   * {@inheritdoc}
   */
  function defineOptions() {
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
  function opSimple($column) {
    $delta = is_numeric($this->options['delta']) ? $this->options['delta'] : NULL;
    $this->query->query->fieldCondition($this->definition['field_name'], $column, $this->value, $this->operator, $delta);
  }

}
