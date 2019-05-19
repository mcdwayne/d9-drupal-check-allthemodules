<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\filter\Date.
 */

namespace Drupal\views_xml_backend\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\Date as ViewsDate;
use Drupal\views_xml_backend\AdminLabelTrait;

/**
 * Date filter implementation.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("views_xml_backend_date")
 */
class Date extends ViewsDate implements XmlFilterInterface {

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
    $options['granularity']['default'] = 'second';

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

    $form['granularity'] = [
      '#type' => 'radios',
      '#title' => $this->t('Granularity'),
      '#options' => [
        'second' => $this->t('Second'),
        'minute' => $this->t('Minute'),
        'hour'   => $this->t('Hour'),
        'day'    => $this->t('Day'),
        'month'  => $this->t('Month'),
        'year'   => $this->t('Year'),
      ],
      '#description' => $this->t('The granularity is the smallest unit to use when determining whether two dates are the same; for example, if the granularity is "Year" then all dates in 1999, regardless of when they fall in 1999, will be considered the same date.'),
      '#default_value' => $this->options['granularity'],
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    $operator = $this->operator;
    $xpath = $this->options['xpath_selector'];
    $granularity = $this->options['granularity'];

    $min = views_xml_backend_date($this->value['min'], $granularity);
    $max = views_xml_backend_date($this->value['max'], $granularity);

    if ($operator === 'between') {
      return "php:functionString('views_xml_backend_date', $xpath, '$granularity') >= $min and php:functionString('views_xml_backend_date', $xpath, '$granularity') <= $max";
    }

    if ($operator === 'not between') {
      return "php:functionString('views_xml_backend_date', $xpath, '$granularity') <= $min and php:functionString('views_xml_backend_date', $xpath, '$granularity') >= $max";
    }

    $value = views_xml_backend_date($this->value['value'], $granularity);

    return "php:functionString('views_xml_backend_date', $xpath, '$granularity') $operator $value";
  }

}
