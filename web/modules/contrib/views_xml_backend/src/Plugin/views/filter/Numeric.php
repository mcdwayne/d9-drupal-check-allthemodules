<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\filter\Numeric.
 */

namespace Drupal\views_xml_backend\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\NumericFilter;
use Drupal\views_xml_backend\AdminLabelTrait;
use Drupal\views_xml_backend\Xpath;

/**
 * Default implementation of the base filter plugin.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_xml_backend_numeric")
 */
class Numeric extends NumericFilter implements XmlFilterInterface {

  use AdminLabelTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->query->addFilter($this);
  }

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $options = parent::defineOptions();

    $options['xpath_selector']['default'] = '';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function operators() {
    $operators = parent::operators();

    unset($operators['regular_expression']);

    return $operators;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['xpath_selector'] = [
      '#type' => 'textfield',
      '#title' => 'XPath selector',
      '#description' => $this->t('The field name in the table that will be used as the filter.'),
      '#default_value' => $this->options['xpath_selector'],
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $operator = $this->operator;
    $xpath = $this->options['xpath_selector'];

    $min = Xpath::escapeXpathString($this->value['min']);
    $max = Xpath::escapeXpathString($this->value['max']);

    if ($operator === 'between') {
      return $xpath . ' >= ' . $min . ' and ' . $xpath . ' <= ' . $max;
    }

    if ($operator === 'not between') {
      return $xpath . ' <= ' . $min . ' or ' . $xpath . ' >= ' . $max;
    }

    return $xpath . ' ' . $operator . ' ' . Xpath::escapeXpathString($this->value['value']);
  }

}
