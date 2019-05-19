<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\sort\Standard.
 */

namespace Drupal\views_xml_backend\Plugin\views\sort;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\sort\SortPluginBase;
use Drupal\views_xml_backend\AdminLabelTrait;
use Drupal\views_xml_backend\Sorter\StringSorter;

/**
 * Default implementation of the base sort plugin.
 *
 * @ingroup views_sort_handlers
 *
 * @ViewsSort("views_xml_backend_standard")
 */
class Standard extends SortPluginBase {

  use AdminLabelTrait;

  /**
   * {@inheritdoc}
   */
  public function query() {
    $alias = 'sort_string_' . $this->options['id'];
    $this->query->addField($alias, $this->options['xpath_selector']);
    $this->query->addSort(new StringSorter($alias, $this->options['order']));
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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $form['xpath_selector'] = [
      '#type' => 'textfield',
      '#title' => 'XPath selector',
      '#description' => $this->t('The field name in the table that will be used for the sort.'),
      '#default_value' => $this->options['xpath_selector'],
      '#required' => TRUE,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

}
