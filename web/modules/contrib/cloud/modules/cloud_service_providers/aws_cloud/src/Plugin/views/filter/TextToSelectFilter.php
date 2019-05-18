<?php

namespace Drupal\aws_cloud\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filter handler which turns a text field filter into a select filter.
 *
 * This class is adapted from the following project.
 * https://www.drupal.org/project/views_field_select_filter
 * The main difference is that this filter takes cloud_context into account
 * if that is available in the url.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsFilter("texttoselect")
 */
class TextToSelectFilter extends InOperator implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    // Default to true.  This filter is not useful
    // if not exposed to the user.
    $options['exposed'] = ['default' => TRUE];
    $options['expose']['contains']['sort_values'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['sort_values'] = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
    $form['expose']['sort_values'] = [
      '#type' => 'radios',
      '#title' => t('Value order'),
      '#description' => t('How to sort the values in the exposed form'),
      '#options' => [0 => $this->t('ASC'), 1 => $this->t('DESC')],
      '#default_value' => $this->options['expose']['sort_values'],
      '#weight' => 1,
    ];
    // Setup the default_value.  we are forcing exposed = true, ajax
    // does not have a chance to update the default values.
    $form['expose']['label']['#default_value'] = $this->definition['title'];
    $form['expose']['identifier']['#default_value'] = $this->options['id'];
  }

  /**
   * No operator options, it's just IN.
   *
   * @return array
   *   Empty Array.
   */
  public function operatorOptions($wich = 'in') {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    // Search for cloud_context from the route.  If found, include that
    // in the query.
    $cloud_context = \Drupal::routeMatch()->getParameter('cloud_context');
    $allFilters = $this->displayHandler->getOption('filters');
    $connection = \Drupal::database();
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select($this->table, 'ft')
      ->fields('ft', [$this->realField])
      ->distinct(TRUE);
    $query->orderBy($this->realField, ($this->options['expose']['sort_values'] == '1') ? 'desc' : 'asc');
    if (isset($allFilters['type'])) {
      $types = array_keys($allFilters['type']['value']);
      $query->condition('bundle', $types, 'in');
    }

    if (isset($cloud_context)) {
      $query->condition('cloud_context', $cloud_context);
    }
    // Filter out null values.
    $query->isNotNull($this->realField);
    $values = $query->execute()->fetchAllKeyed(0, 0);
    $this->valueOptions = $values;
  }

  /**
   * Only show the value form in the exposed form.
   *
   * For now there is no support
   * for limiting the number of selectable options.
   *
   * {@inheritdoc}
   *
   * @param array $form
   *   Array of form elements.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function valueForm(array &$form, FormStateInterface $form_state) {
    $form['value'] = [];
    $this->getValueOptions();
    $options = $this->valueOptions;
    if (count($options) && $form_state->get('exposed')) {
      parent::valueForm($form, $form_state);
    }
  }

}
