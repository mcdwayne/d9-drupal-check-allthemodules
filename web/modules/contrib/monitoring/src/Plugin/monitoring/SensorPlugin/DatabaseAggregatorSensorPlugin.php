<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DatabaseAggregatorSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Drupal\monitoring\SensorPlugin\DatabaseAggregatorSensorPluginBase;
use Drupal\Core\Entity\DependencyTrait;

/**
 * Database aggregator able to query a single db table.
 *
 * @SensorPlugin(
 *   id = "database_aggregator",
 *   label = @Translation("Database Aggregator"),
 *   description = @Translation("Database aggregator able to query a single db table."),
 *   addable = TRUE
 * )
 */
class DatabaseAggregatorSensorPlugin extends DatabaseAggregatorSensorPluginBase implements ExtendedInfoSensorPluginInterface {

  use DependencyTrait;

  /**
   * The query string of the executed query.
   *
   * @var object
   */
  protected $queryString;

  /**
   * The arguments of the executed query.
   *
   * @var array
   */
  protected $queryArguments;

  /**
   * The arguments of the executed query.
   *
   * @var \Drupal\Core\Database\StatementInterface
   */
  protected $executedQuery;

  /**
   * The fetched object from the query result.
   *
   * @var mixed
   */
  protected $fetchedObject;

  /**
   * Allows plugins to control if the conditions table should be shown.
   *
   * @var bool
   */
  protected $configurableConditions = TRUE;

  /**
   * Allows plugins to control if the verbose output table should be shown.
   *
   * @var bool
   */
  protected $configurableVerboseOutput = TRUE;

  /**
   * Allows plugins to control if the table can be configured.
   *
   * @var bool
   */
  protected $configurableTable = TRUE;

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = parent::getDefaultConfiguration();
    $default_config['settings'] = [
      'history_status' => TRUE,
    ];
    return $default_config;
  }

  /**
   * Builds simple aggregate query over one db table.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   The select query object.
   */
  protected function getAggregateQuery() {
    /* @var \Drupal\Core\Database\Connection $database */
    $database = $this->getService('database');
    // Get aggregate query for the table.
    $query = $database->select($this->sensorConfig->getSetting('table'));

    $this->addAggregateExpression($query);

    // Add conditions.
    foreach ($this->getConditions() as $condition) {
      $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
    }

    // Apply time interval on field.
    if ($this->getTimeIntervalField() && $this->getTimeIntervalValue()) {
      $query->condition($this->getTimeIntervalField(), REQUEST_TIME - $this->getTimeIntervalValue(), '>');
    }

    return $query;
  }

  /**
   * Adds the aggregate expression to the select query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $select
   *   The database select query.
   */
  protected function addAggregateExpression(SelectInterface $select) {
    $select->addExpression('COUNT(*)', 'records_count');
  }

  /**
   * Builds the  query for verbose output.
   *
   * Similar to the aggregate query, but without aggregation.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   The select query object.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\DatabaseAggregatorSensorPlugin::getAggregateQuery()
   */
  protected function getQuery() {
    /* @var \Drupal\Core\Database\Connection $database */
    $database = $this->getService('database');
    // Get query for the table.
    $query = $database->select($this->sensorConfig->getSetting('table'));
    // Add conditions.
    foreach ($this->getConditions() as $condition) {
      $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
    }
    // Apply time interval on field.
    if ($this->getTimeIntervalField() && $this->getTimeIntervalValue()) {
      $query->condition($this->getTimeIntervalField(), REQUEST_TIME - $this->getTimeIntervalValue(), '>');
    }

    // Add key fields.
    $fields = $this->sensorConfig->getSetting('verbose_fields');
    if (!empty($fields)) {
      foreach ($fields as $field) {
        $query->addField($this->sensorConfig->getSetting('table'), $field);
      }
    }

    if ($this->getTimeIntervalField()) {
      $query->orderBy($this->getTimeIntervalField(), 'DESC');
    }

    return $query;
  }

  /**
   * Builds history query over one db table.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   The select query object.
   */
  protected function getHistoryQuery() {
    /* @var \Drupal\Core\Database\Connection $database */
    $database = $this->getService('database');
    // Get aggregate query for the table.
    $query = $database->select($this->sensorConfig->getSetting('table'));

    // Add conditions.
    foreach ($this->getConditions() as $condition) {
      $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
    }

    // Group by time intervals of timestamp.
    $query->addExpression('MIN(' . $this->getTimeIntervalField() . ') DIV ' . $this->getTimeIntervalValue() . ' * ' . $this->getTimeIntervalValue(), 'timestamp');
    $query->groupBy($this->getTimeIntervalField() . ' DIV ' . $this->getTimeIntervalValue());

    $this->addAggregateExpression($query);

    return $query;
  }

  /**
   * Get the timestamp of the oldest entry that fits owr conditions.
   *
   * @return \Drupal\Core\Database\Query\Select
   *   The timestamp of the oldest entry.
   */
  protected function getOldestEntry() {
    /* @var \Drupal\Core\Database\Connection $database */
    $database = $this->getService('database');
    $query = $database->select($this->sensorConfig->getSetting('table'));

    // Add conditions.
    foreach ($this->getConditions() as $condition) {
      $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
    }

    // Get the oldest entry.
    $query->addExpression('MIN(' . $this->getTimeIntervalField() . ')', 'timestamp');

    return $query->range(0, 1)->execute()->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = [];

    if ($this->sensorConfig->getSetting('verbose_fields')) {
      $this->verboseResultUnaggregated($output);
    }
    if ($this->sensorConfig->getSetting('history_status')) {
      $this->verboseResultHistory($output);
    }

    return $output;
  }

  /**
   * Adds unaggregated verbose output to the render array $output.
   *
   * @param array &$output
   *   Render array where the result will be added.
   */
  public function verboseResultUnaggregated(array &$output) {
    $output['verbose_sensor_result'] = array(
      '#type' => 'verbose_table_result',
      '#title' => t('Unaggregated result'),
    );

    // Fetch the last 10 matching entries, unaggregated.
    $query = $this->getQuery();
    $query_result = $query
      ->range(0, 10)
      ->execute();

    $rows = $this->buildTableRows($query_result->fetchAll());

    // Reformat the timestamp.
    $fields = $this->sensorConfig->getSetting('verbose_fields');
    $timestamp_field_name = $this->getTimeIntervalField();
    if ($timestamp_field_name && in_array($timestamp_field_name, $fields)) {
      foreach ($rows as $key => $row) {
        $rows[$key][$timestamp_field_name] = \Drupal::service('date.formatter')->format($row[$timestamp_field_name], 'short');
      }
    }

    $output['verbose_sensor_result']['#header'] = $this->buildTableHeader($rows);
    $output['verbose_sensor_result']['#rows'] = $rows;

    // Show query.
    $output['verbose_sensor_result']['#query'] = $query_result->getQueryString();
    $output['verbose_sensor_result']['#query_args'] = $query->getArguments();
  }

  /**
   * Adds history verbose output to the render array $output.
   *
   * @param array &$output
   *   Render array where the result will be added.
   */
  public function verboseResultHistory(array &$output) {
    $output['verbose_sensor_history'] = array(
      '#type' => 'verbose_table_result',
      '#title' => t('History'),
    );

    // Fetch the last 10 matching entries, aggregated.
    $query = $this->getHistoryQuery();
    $query_result = $query->range(0, 10)->execute();
    $rows = $this->buildTableRows($query_result->fetchAll());

    // Reformat the timestamp.
    foreach ($rows as $key => $row) {
      $rows[$key]['timestamp'] = \Drupal::service('date.formatter')->format($row['timestamp'], 'short');
    }

    $output['verbose_sensor_history']['#header'] = $this->buildTableHeader($rows);
    $output['verbose_sensor_history']['#rows'] = $rows;

    // Show query.
    $output['verbose_sensor_history']['#query'] = $query_result->getQueryString();
    $output['verbose_sensor_history']['#query_args'] = $query->getArguments();

    // Add oldest record info message.
    if ($oldest_entry = $this->getOldestEntry()) {
      $output['verbose_sensor_history']['#description'] = t('Oldest timestamp record is from :oldest_timestamp', [
        ':oldest_timestamp' => \Drupal::service('date.formatter')->format($oldest_entry, 'short'),
      ]);
    }
  }

  /**
   * Builds the header for a table based on rows.
   *
   * Do not override this method to define a static header.
   *
   * @param array $rows
   *   The array of rows for which a header will be built.
   *
   * @return array $header
   *   The associative header array for the table.
   */
  protected function buildTableHeader($rows = []) {
    if (empty($rows)) {
      return [];
    }
    // Provide consistent keys for header and data rows for easy altering.
    $keys = array_keys($rows[0]);
    $header = array_combine($keys, $keys);
    return $header;
  }

  /**
   * Builds the rows of a table.
   *
   * @param array $results
   *   Array of query results.
   *
   * @return array $rows
   *   The render array with the table rows.
   */
  protected function buildTableRows(array $results) {
    $rows = [];
    foreach ($results as $delta => $row) {
      $rows[$delta] = (array) $row;
    }

    return $rows;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $query = $this->getAggregateQuery();
    $this->queryArguments = $query->getArguments();
    $this->executedQuery = $query->execute();
    $this->queryString = $this->executedQuery->getQueryString();
    $this->fetchedObject = $this->executedQuery->fetchObject();

    $records_count = 0;
    if (!empty($this->fetchedObject->records_count)) {
      $records_count = $this->fetchedObject->records_count;
    }

    $result->setValue($records_count);
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // There is no API to load a list of all tables, loop through all modules
    // with a hook_schema() hook and try to find the table.
    \Drupal::moduleHandler()->loadAllIncludes('install');
    foreach (\Drupal::moduleHandler()->getImplementations('schema') as $module) {
      $schema = drupal_get_module_schema($module, $this->sensorConfig->getSetting('table'));
      if (isset($schema['module'])) {
        $this->addDependency('module', $schema['module']);
        break;
      }
    }
    return $this->dependencies;
  }

  /**
   * Adds UI for variables table, conditions and keys.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['table'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->sensorConfig->getSetting('table'),
      '#maxlength' => 255,
      '#title' => t('Table'),
      '#required' => TRUE,
      '#access' => $this->configurableTable,
    );

    // Add conditions.
    // Fieldset for sensor list elements.
    $form['conditions_table'] = array(
      '#type' => 'fieldset',
      '#title' => t('Conditions'),
      '#prefix' => '<div id="selected-conditions">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
      '#access' => $this->configurableConditions,
    );

    // Table for included sensors.
    $form['conditions_table']['conditions'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array(
        'field' => t('Field key'),
        'operator' => t('Operator'),
        'value' => t('Value'),
      ),
      '#empty' => t('Add conditions to filter the results.'),
    );

    // Fill the conditions table with keys and values for each condition.
    $conditions = (array) $this->sensorConfig->getSetting('conditions');

    if (!$form_state->has('conditions_rows')) {
      $form_state->set('conditions_rows', count($conditions) + 1);
    }

    for ($i = 0; $i < $form_state->get('conditions_rows'); $i++) {
      $condition = isset($conditions[$i]) ? $conditions[$i] : array();

      $condition += array(
        'field' => '',
        'value' => '',
        'operator' => '=',
      );

      $form['conditions_table']['conditions'][$i] = array(
        'field' => array(
          '#type' => 'textfield',
          '#default_value' => $condition['field'],
          '#title' => t('Field'),
          '#title_display' => 'invisible',
          '#size' => 20,
          //'#required' => TRUE,
        ),
        'operator' => array(
          '#type' => 'select',
          '#default_value' => $condition['operator'],
          '#title' => t('Operator'),
          '#title_display' => 'invisible',
          '#options' => $this->getConditionsOperators(),
          //'#required' => TRUE,
        ),
        'value' => array(
          '#type' => 'textfield',
          '#default_value' => $condition['value'],
          '#title' => t('Value'),
          '#title_display' => 'invisible',
          '#size' => 40,
          //'#required' => TRUE,
        ),
      );
    }

    // Select element for available conditions.
    $form['conditions_table']['condition_add_button'] = array(
      '#type' => 'submit',
      '#value' => t('Add another condition'),
      '#ajax' => array(
        'wrapper' => 'selected-conditions',
        'callback' => array($this, 'conditionsReplace'),
        'method' => 'replace',
      ),
      '#submit' => array(array($this, 'addConditionSubmit')),
    );

    // Add a fieldset to filter verbose output by fields.
    $form['output_table'] = array(
      '#type' => 'fieldset',
      '#title' => t('Verbose Fields'),
      '#prefix' => '<div id="selected-output">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
      '#access' => $this->configurableVerboseOutput,
    );
    // Add a table for the fields.
    $form['output_table']['verbose_fields'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array(
        'field_key' => t('Field key'),
      ),
      '#title' => t('Verbose fields'),
      '#empty' => t('Add keys to display in the verbose output.'),
    );

    // Fill the fields table with verbose fields to filter the output.
    $fields = (array) $this->sensorConfig->getSetting('verbose_fields');

    if (!$form_state->has('fields_rows')) {
      $form_state->set('fields_rows', count($fields) + 1);
    }
    for ($i = 0; $i < $form_state->get('fields_rows'); $i++) {
      $field = isset($fields[$i]) ? $fields[$i] : $i;
      $form['output_table']['verbose_fields'][$field] = array(
        // This table only has one column called 'field_key'.
        'field_key' => array(
          '#type' => 'textfield',
          '#default_value' => (is_int($field)) ? '' : $field,
          '#size' => 20,
        ),
      );
    }
    // Select element for available fields.
    $form['output_table']['fields_add_button'] = array(
      '#type' => 'submit',
      '#value' => t('Add another field'),
      '#ajax' => array(
        'wrapper' => 'selected-output',
        'callback' => array($this, 'fieldsReplace'),
        'method' => 'replace',
      ),
      '#submit' => array(array($this, 'addFieldSubmit')),
    );

    // Enable history results in time aggregation.
    $form['aggregation']['history_status'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->sensorConfig->getSetting('history_status'),
      '#title' => t('Enable history'),
      '#description' => t('Check to show history results.'),
    ];

    // Always show the enable history checkbox if a timestamp field is forced,
    // otherwise add states so it is only visible if something is entered.
    if ($this->configurableTimestampField) {
      $form['aggregation']['history_status']['#states'] = [
        'invisible' => [
          ':input[name="settings[aggregation][time_interval_field]"]' => ['value' => ''],
        ],
      ];
    }

    return $form;
  }

  /**
   * Provides list of operators for conditions.
   *
   * @return array
   *   The operators supported.
   */
  protected function getConditionsOperators() {
    // See operators https://api.drupal.org/api/drupal/includes%21entity.inc/function/EntityFieldQuery%3A%3AaddFieldCondition/7
    return array(
      '=' => t('='),
      '!=' => t('!='),
      '<' => t('<'),
      '=<' => t('=<'),
      '>' => t('>'),
      '>=' => t('>='),
      'STARTS_WITH' => t('STARTS_WITH'),
      'CONTAINS' => t('CONTAINS'),
      //'BETWEEN' => t('BETWEEN'), // Requires
      //'IN' => t('IN'),
      //'NOT IN' => t('NOT IN'),
      //'EXISTS' => t('EXISTS'),
      //'NOT EXISTS' => t('NOT EXISTS'),
      //'LIKE' => t('LIKE'),
      //'IS NULL' => t('IS NULL'),
      //'IS NOT NULL' => t('IS NOT NULL'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $settings = $this->sensorConfig->getSettings();

    // Cleanup conditions, remove empty.
    $settings['conditions'] = [];
    foreach ($form_state->getValue('conditions', []) as $key => $condition) {
      if (!empty($condition['field'])) {
        $settings['conditions'][] = $condition;
      }
    }

    // Update the verbose output fields.
    $settings['verbose_fields'] = [];
    foreach ($form_state->getValue('verbose_fields', []) as $field) {
      if (!empty($field['field_key'])) {
        $settings['verbose_fields'][] = $field['field_key'];
      }
    }

    // Update the history status.
    if ($form_state->getValue('settings')['aggregation']['time_interval_field']) {
      $settings['history_status'] = $form_state->getValue('settings')['aggregation']['history_status'];
    }
    else {
      $settings['history_status'] = FALSE;
    }

    $this->sensorConfig->set('settings', $settings);
  }

  /**
   * Returns the updated 'conditions' fieldset for replacement by ajax.
   *
   * @param array $form
   *   The updated form structure array.
   * @param FormStateInterface $form_state
   *   The form state structure.
   *
   * @return array
   *   The updated form component for the selected fields.
   */
  public function conditionsReplace(array $form, FormStateInterface $form_state) {
    return $form['plugin_container']['settings']['conditions_table'];
  }

  /**
   * Add row to table when pressing 'Add another condition' and rebuild.
   *
   * @param array $form
   *   The form structure array.
   * @param FormStateInterface $form_state
   *   The form state structure.
   */
  public function addConditionSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $form_state->set('conditions_rows', $form_state->get('conditions_rows') + 1);

    drupal_set_message(t('Condition added.'), 'status');
  }

  /**
   * Returns the updated 'output_table' fieldset for replacement by ajax.
   *
   * @param array $form
   *   The updated form structure array.
   * @param FormStateInterface $form_state
   *   The form state structure.
   *
   * @return array
   *   The updated form component for the selected fields.
   */
  public function fieldsReplace(array $form, FormStateInterface $form_state) {
    return $form['plugin_container']['settings']['output_table'];
  }

  /**
   * Adds another field to the keys table when pressing 'Add another key'.
   *
   * @param array $form
   *   The form structure array.
   * @param FormStateInterface $form_state
   *   The form state structure.
   */
  public function addFieldSubmit(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild();

    $form_state->set('fields_rows', $form_state->get('fields_rows') + 1);
    drupal_set_message(t('Field added.'), 'status');
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    /** @var \Drupal\Core\Database\Connection $database */
    $database = $this->getService('database');
    $table = $form_state->getValue(array('settings', 'table'));
    $query = $database->select($table);
    if (!$database->schema()->tableExists($table)) {
      try {
        $query->range(0, 1)->execute();
      }
      catch (\Exception $e) {
        $form_state->setErrorByName('settings][table', t('The table %table does not exist in the database %database', ['%table' => $table, '%database' => $database->getConnectionOptions()['database']]));
        return;
      }
    }
    $field_name = $form_state->getValue(array(
      'settings',
      'aggregation',
      'time_interval_field',
    ));
    if (!empty($field_name)) {
      // @todo instead of validate, switch to a form select.
      if (!$database->schema()->fieldExists($table, $field_name)) {
        $form_state->setErrorByName('settings][aggregation][time_interval_field',
          t('The specified time interval field %name does not exist in table %table.', array('%name' => $field_name, '%table' => $table)));
      }
    }

    // Validate verbose_fields.
    if ($this->configurableConditions) {
      $fields = $form_state->getValue('verbose_fields', []);
      foreach ($fields as $key => $field) {
        $query = $database->select($table);
        $field_name = $field['field_key'];
        if (!empty($field_name) && !$database->schema()->fieldExists($table, $field_name)) {
          $query->addField($table, $field_name);
          try {
            $query->range(0, 1)->execute();
          }
          catch (\Exception $e) {
            $form_state->setErrorByName("verbose_fields][$key][field_key", t('The field %field does not exist in the table "%table".', ['%field' => $field_name, '%table' => $table]));
            continue;
          }
        }
      }
      // Validate conditions.
      $fields = $form_state->getValue('conditions', []);
      foreach ($fields as $key => $field) {
        $query = $database->select($table);
        $field_name = $field['field'];
        if (!empty($field_name) && !$database->schema()->fieldExists($table, $field_name)) {
          $query->addField($table, $field_name);
          try {
            $query->range(0, 1)->execute();
          }
          catch (\Exception $e) {
            $form_state->setErrorByName("conditions][$key][field", t('The field %field does not exist in the table "%table".', ['%field' => $field_name, '%table' => $table]));
            continue;
          }
        }
      }
    }
  }

}
