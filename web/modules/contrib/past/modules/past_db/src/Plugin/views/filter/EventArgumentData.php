<?php

namespace Drupal\past_db\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * Filter handler which filter the event's argument key value.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("past_db_event_argument_data")
 */
class EventArgumentData extends StringFilter {

  /**
   * Contains the actual value of the argument_name field.
   *
   * @var string
   */
  public $argumentName = NULL;

  /**
   * Contains the actual value of the data_key field.
   *
   * @var string
   */
  public $dataKey = NULL;

  /**
   * Where the $query object will reside.
   *
   * @var \Drupal\views\Plugin\views\query\Sql
   */
  public $query = NULL;

  /**
   * {@inheritdoc}
   */
  public function defineOptions() {
    $definition = parent::defineOptions();
    $definition['argument_name'] = ['default' => ''];
    $definition['data_key'] = ['default' => ''];
    $definition['expose']['contains']['argument_name_expose'] = ['default' => TRUE];
    $definition['expose']['contains']['data_key_expose'] = ['default' => TRUE];
    return $definition;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->argumentName = $this->options['argument_name'];
    $this->dataKey = $this->options['data_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function acceptExposedInput($input) {
    $result = parent::acceptExposedInput($input);
    if (isset($input['argument_name']) && $this->options['expose']['argument_name_expose']) {
      $this->argumentName = $input['argument_name'];
      $result = TRUE;
    }
    if (isset($input['data_key']) && $this->options['expose']['data_key_expose']) {
      $this->dataKey = $input['data_key'];
      $result = TRUE;
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $this->argumentNameForm($form, $form_state);
    $this->dataKeyForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposedForm($form, $form_state);

    $form['expose']['argument_name_expose'] = [
      '#type' => 'checkbox',
      '#title' => t('Expose argument name'),
      '#default_value' => $this->options['expose']['argument_name_expose'],
    ];

    $form['expose']['data_key_expose'] = [
      '#type' => 'checkbox',
      '#title' => t('Expose data key'),
      '#default_value' => $this->options['expose']['data_key_expose'],
    ];
  }

  /**
   * Provide a simple textfield for argument name.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function argumentNameForm(array &$form, FormStateInterface $form_state) {
    $form['argument_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Argument name'),
      '#size' => 30,
      '#default_value' => $this->argumentName,
    ];
  }

  /**
   * Provide a simple textfield for data key.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  protected function dataKeyForm(array &$form, FormStateInterface $form_state) {
    $form['data_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Data key'),
      '#size' => 30,
      '#default_value' => $this->dataKey,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    // Add the join for the argument table using a new relationship.
    $configuration = [
      'table' => 'past_event_argument',
      'field' => 'event_id',
      'left_table' => 'past_event',
      'left_field' => 'event_id',
      'operator' => '=',
    ];
    /** @var \Drupal\views\Plugin\views\join\Standard $join */
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $relationship_alias = $this->query->addRelationship($this->options['id'], $join, 'past_event');

    // Limit to a specific argument, if configured.
    if ($this->argumentName) {
      $this->query->addWhere($this->options['group'], $relationship_alias . '.name', $this->argumentName, '=');
    }

    // Join the data table using the specified relationship.
    $configuration = [
      'table' => 'past_event_data',
      'field' => 'argument_id',
      'left_table' => 'past_event_argument',
      'left_field' => 'argument_id',
      'operator' => '=',
    ];
    $join = Views::pluginManager('join')->createInstance('standard', $configuration);
    $this->tableAlias = $this->query->addTable('past_event', $relationship_alias, $join);

    // Limit to a specific data key, if configured.
    if ($this->dataKey) {
      $this->query->addWhere($this->options['group'], $relationship_alias . '.name', $this->dataKey, '=');
    }
    $this->realField = 'value';
    parent::query();
  }

}
