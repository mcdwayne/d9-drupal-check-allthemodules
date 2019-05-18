<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ContentEntityAggregatorSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Core\Database\Database;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\Query\QueryAggregateInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\DatabaseAggregatorSensorPluginBase;
use Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Content entity database aggregator.
 *
 * It utilises the entity query aggregate functionality.
 *
 * @SensorPlugin(
 *   id = "entity_aggregator",
 *   label = @Translation("Content Entity Aggregator"),
 *   description = @Translation("Utilises the entity query aggregate functionality."),
 *   addable = TRUE
 * )
 */
class ContentEntityAggregatorSensorPlugin extends DatabaseAggregatorSensorPluginBase implements ExtendedInfoSensorPluginInterface {

  use DependencySerializationTrait;
  use DependencyTrait;

  /**
   * Local variable to store the field that is used as aggregate.
   *
   * @var string
   *   Field name.
   */
  protected $aggregateField;

  /**
   * Local variable to store \Drupal::entityManger().
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Allows plugins to control if the entity type can be configured.
   *
   * @var bool
   */
  protected $configurableEntityType = TRUE;

  /**
   * Builds the entity aggregate query.
   *
   * @return \Drupal\Core\Entity\Query\QueryAggregateInterface
   *   The entity query object.
   */
  protected function getEntityQueryAggregate() {
    $entity_info = $this->entityManager->getDefinition($this->sensorConfig->getSetting('entity_type'), TRUE);

    // Get aggregate query for the entity type.
    $query = $this->entityManager->getStorage($this->sensorConfig->getSetting('entity_type'))->getAggregateQuery();
    $this->aggregateField = $entity_info->getKey('id');

    $this->addAggregate($query);

    // Add conditions.
    foreach ($this->getConditions() as $condition) {
      if (empty($condition['field'])) {
        continue;
      }
      $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
    }

    // Apply time interval on field.
    if ($this->getTimeIntervalField() && $this->getTimeIntervalValue()) {
      $query->condition($this->getTimeIntervalField(), REQUEST_TIME - $this->getTimeIntervalValue(), '>');
    }

    return $query;
  }

  /**
   * Builds the entity query for verbose output.
   *
   * Similar to the aggregate query, but without aggregation.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query object.
   *
   * @see \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ContentEntityAggregatorSensorPlugin::getEntityQueryAggregate()
   */
  protected function getEntityQuery() {
    $entity_info = $this->entityManager->getDefinition($this->sensorConfig->getSetting('entity_type'), TRUE);

    // Get query for the entity type.
    $query = $this->entityManager->getStorage($this->sensorConfig->getSetting('entity_type'))->getQuery();
    // Add conditions.
    foreach ($this->getConditions() as $condition) {
      if (empty($condition['field'])) {
        continue;
      }
      $query->condition($condition['field'], $condition['value'], isset($condition['operator']) ? $condition['operator'] : NULL);
    }

    // Apply time interval on field.
    if ($this->getTimeIntervalField() && $this->getTimeIntervalValue()) {
      $query->condition($this->getTimeIntervalField(), REQUEST_TIME - $this->getTimeIntervalValue(), '>');
    }

    // Order by most recent or id.
    if ($this->getTimeIntervalField()) {
      $query->sort($this->getTimeIntervalField(), 'DESC');
    }
    else {
      $query->sort($entity_info->getKey('id'), 'DESC');
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition, EntityManagerInterface $entityManager) {
    parent::__construct($sensor_config, $plugin_id, $plugin_definition);
    $this->entityManager = $entityManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    return new static(
      $sensor_config,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    $default_config = array(
      'settings' => array(
        'entity_type' => 'node',
      ),
    );
    return $default_config;
  }

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $query_result = $this->getEntityQueryAggregate()->execute();
    $entity_type = $this->sensorConfig->getSetting('entity_type');
    $entity_info = $this->entityManager->getDefinition($entity_type);

    if (isset($query_result[0][$entity_info->getKey('id') . '_count'])) {
      $records_count = $query_result[0][$entity_info->getKey('id') . '_count'];
    }
    else {
      $records_count = 0;
    }

    $result->setValue($records_count);
  }

  /**
   * {@inheritdoc}
   */
  public function resultVerbose(SensorResultInterface $result) {
    $output = [];

    if ($this->sensorConfig->getSetting('verbose_fields')) {
      $this->verboseResultUnaggregated($output);
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
    $output = [];

    /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager */
    $field_type_manager = \Drupal::service('plugin.manager.field.field_type');
    Database::startLog('monitoring_ceasp');

    // Fetch the last 10 matching entries, unaggregated.
    $entity_ids = $this->getEntityQuery()
      ->range(0, 10)
      ->execute();

    // Show query.
    $query_log = Database::getLog('monitoring_ceasp')[0];

    // Load entities.
    $entity_type_id = $this->sensorConfig->getSetting('entity_type');;
    $entities = $this->entityManager
      ->getStorage($entity_type_id)
      ->loadMultiple($entity_ids);

    // Get the fields to display from the settings.
    $fields = $this->sensorConfig->getSetting('verbose_fields', ['id', 'label']);

    // Render entities.
    $rows = [];
    /* @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    foreach ($entities as $id => $entity) {
      $row = [];
      foreach ($fields as $field) {
        switch ($field) {
          case 'id':
            $row[] = $entity->id();
            break;

          case $this->getTimeIntervalField():
            $row[] = \Drupal::service('date.formatter')->format($entity->get($this->getTimeIntervalField())[0]->value, 'short');
            break;

          case 'label':
            $row[] = $entity->hasLinkTemplate('canonical') ? $entity->link() : $entity->label();
            break;

          default:
            // Make sure the field exists on this entity.
            if ($entity instanceof FieldableEntityInterface && $entity->hasField($field)) {
              try {
                // Get the main property as a fallback if the field can not be
                // viewed.
                $field_type = $entity->getFieldDefinition($field)->getFieldStorageDefinition()->getType();
                // If the field type has a default formatter, try to view it.
                if (isset($field_type_manager->getDefinition($field_type)['default_formatter'])) {
                  $value = $entity->$field->view(['label' => 'hidden']);
                  $row[] = \Drupal::service('renderer')->renderPlain($value);
                }
                else {
                  // Fall back to the main property.
                  $property = $entity->getFieldDefinition($field)->getFieldStorageDefinition()->getMainPropertyName();
                  $row[] = SafeMarkup::checkPlain($entity->$field->$property);
                }
              } catch (\Exception $e) {
                // Catch any exception and display as an error.
                drupal_set_message(t('Error while trying to display %field: @error', ['%field' => $field, '@error' => $e->getMessage()]), 'error');
                $row[] = '';
              }
            }
            else {
              $row[] = '';
            }
            break;
        }
      }

      $rows[] = array(
        'data' => $row,
        'class' => 'entity',
      );
    }
    $header = $this->sensorConfig->getSetting('verbose_fields', [
      'id',
      'label'
    ]);
    $output['entities'] = array(
      '#type' => 'verbose_table_result',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => t('No matching entities were found.'),
      '#query' => $query_log['query'],
      '#query_args' => $query_log['args'],
    );

    return $output;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $entity_type_id = $this->sensorConfig->getSetting('entity_type');
    if (!$entity_type_id) {
      throw new \Exception(new FormattableMarkup('Sensor @id is missing the required entity_type setting.', array('@id' => $this->id())));
    }
    $entity_type = $this->entityManager->getDefinition($this->sensorConfig->getSetting('entity_type'));
    $this->addDependency('module', $entity_type->getProvider());
    return $this->dependencies;
  }

  /**
   * Adds UI for variables entity_type, conditions and verbose_fields.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $settings = $this->sensorConfig->getSettings();
    $entity_types = $this->entityManager->getEntityTypeLabels();
    $options = [];
    foreach ($entity_types as $id => $label) {
      $class = $this->entityManager->getDefinition($id)->getClass();
      if (is_subclass_of($class, '\Drupal\Core\Entity\FieldableEntityInterface')) {
        $options[$id] = $label;
      };
    }

    $form['entity_type'] = array(
      '#type' => 'select',
      '#default_value' => $this->sensorConfig->getSetting('entity_type', 'node'),
      '#maxlength' => 255,
      '#options' => $options,
      '#title' => t('Entity Type'),
      '#ajax' => array(
        'callback' => array($this, 'fieldsReplace'),
        'wrapper' => 'selected-output',
        'method' => 'replace',
      ),
      '#access' => $this->configurableEntityType,
    );
    if (!isset($settings['entity_type'])) {
      $form['entity_type']['#required'] = TRUE;
    }


    // Add conditions.
    // Fieldset for sensor list elements.
    $form['conditions_table'] = array(
      '#type' => 'fieldset',
      '#title' => t('Conditions'),
      '#prefix' => '<div id="selected-conditions">',
      '#suffix' => '</div>',
      '#tree' => FALSE,
    );

    // Table for included sensors.
    $form['conditions_table']['conditions'] = array(
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => array(
        'field' => t('Field'),
        'operator' => t('Operator'),
        'value' => t('Value'),
      ),
      '#empty' => t(
        'Add conditions to filter the results.'
      ),
    );

    // Fill the sensors table with form elements for each sensor.
    $conditions = array_values($this->sensorConfig->getSetting('conditions', []));
    if (empty($conditions)) {
      $conditions = [];
    }

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

      // See operators https://api.drupal.org/api/drupal/includes%21entity.inc/function/EntityFieldQuery%3A%3AaddFieldCondition/7
      $operators = array(
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
          '#options' => $operators,
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

    // Fill the sensors table with form elements for each sensor.
    $form['verbose_fields'] = array(
      '#type' => 'details',
      '#title' => t('Verbose Output configuration'),
      '#prefix' => '<div id="selected-output">',
      '#suffix' => '</div>',
      '#open' => TRUE,
    );
    $entity_type = $this->entityManager->getDefinition($this->sensorConfig->getSetting('entity_type'));
    $available_fields = array_merge(['id', 'label'], array_keys($this->entityManager->getBaseFieldDefinitions($entity_type->id())));
    sort($available_fields);
    $form['verbose_fields']['#description'] = t('Available Fields for entity type %type: %fields.', [
      '%type' => $entity_type->getLabel(),
      '%fields' => implode(', ', $available_fields)
    ]);

    // Fill the sensors table with form elements for each sensor.
    $fields = $this->sensorConfig->getSetting('verbose_fields', ['id', 'label']);
    if (!$form_state->has('fields_rows')) {
      $form_state->set('fields_rows', count($fields) + 1);
    }

    for ($i = 0; $i < $form_state->get('fields_rows'); $i++) {
      $form['verbose_fields'][$i] = [
        '#type' => 'textfield',
        '#default_value' => isset($fields[$i]) ? $fields[$i] : '',
        '#maxlength' => 256,
        '#required' => FALSE,
        '#tree' => TRUE,
      ];
    }

    $form['verbose_fields']['field_add_button'] = array(
      '#type' => 'submit',
      '#value' => t('Add another field'),
      '#ajax' => array(
        'wrapper' => 'selected-output',
        'callback' => array($this, 'fieldsReplace'),
        'method' => 'replace',
      ),
      '#submit' => array(array($this, 'addFieldSubmit')),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    /** @var \Drupal\monitoring\Form\SensorForm $sensor_form */
    $sensor_form = $form_state->getFormObject();
    /** @var \Drupal\monitoring\SensorConfigInterface $sensor_config */
    $sensor_config = $sensor_form->getEntity();
    $settings = $sensor_config->getSettings();

    // Cleanup conditions, remove empty.
    $settings['conditions'] = [];
    foreach ($form_state->getValue('conditions') as $key => $condition) {
      if (!empty($condition['field'])) {
        $settings['conditions'][] = $condition;
      }
    }
    $verbose_fields = [];
    foreach ($form_state->getValue('settings')['verbose_fields'] as $key => $field) {
      if (!empty($field)) {
        $verbose_fields[] = $field;
      }
    };
    $settings['verbose_fields'] = array_unique($verbose_fields);
    $sensor_config->set('settings', $settings);
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
   * Adds sensor to entity when 'Add another condition' button is pressed.
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
   * Returns the updated 'verbose_fields' fieldset for replacement by ajax.
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
    return $form['plugin_container']['settings']['verbose_fields'];
  }

  /**
   * Adds sensor to entity when 'Add another field' button is pressed.
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

    $field_name = $form_state->getValue(array(
      'settings',
      'aggregation',
      'time_interval_field',
    ));
    $entity_type_id = $form_state->getValue(array('settings', 'entity_type'));
    if (!empty($field_name) && !empty($entity_type_id)) {
      // @todo instead of validate, switch to a form select.
      $entity_info = $this->entityManager->getFieldStorageDefinitions($entity_type_id);
      $data_type = NULL;
      if (!empty($entity_info[$field_name])) {
        $data_type = $entity_info[$field_name]->getPropertyDefinition('value')->getDataType();

      }
      if ($data_type != 'timestamp') {
        $form_state->setErrorByName('settings][aggregation][time_interval_field',
          t('The specified time interval field %name does not exist or is not type timestamp.', array('%name' => $field_name)));
      }
    }
  }

  /**
   * Add aggregation to the query.
   *
   * @param \Drupal\Core\Entity\Query\QueryAggregateInterface $query
   *   The query.
   */
  protected function addAggregate(QueryAggregateInterface $query) {
    $query->aggregate($this->aggregateField, 'COUNT');
  }

}
