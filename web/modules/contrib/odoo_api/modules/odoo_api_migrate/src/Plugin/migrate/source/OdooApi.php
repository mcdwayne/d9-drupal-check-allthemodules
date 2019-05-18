<?php

namespace Drupal\odoo_api_migrate\Plugin\migrate\source;

use DateTimeZone;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\odoo_api\OdooApi\ClientInterface;
use Drupal\odoo_api\OdooApi\Data\ModelFieldsResolverInterface;
use Drupal\odoo_api_migrate\OdooCronMigrationSourceInterface;
use Drupal\odoo_api_migrate\OdooHighwaterSourceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides general-purpose Odoo API source plugin.
 *
 * Usage example:
 *
 * @code
 * source:
 *   plugin: odoo_api
 *   model: sale.order
 *   filters:
 *     -
 *       - state
 *       - 'in'
 *       -
 *         - sale
 *         - done
 *         - cancel
 *     -
 *       - some_field
 *       - '='
 *       - some_value
 *   fields:
 *     - state
 *     - picking_ids
 *   join:
 *     -
 *       target_model: 'stock.picking'
 *       base_field: 'picking_ids'
 *       fields:
 *         - state
 *
 * @endcode
 *
 * @see \Drupal\odoo_api\OdooApi\ClientInterface
 *
 * @MigrateSource(
 *  id = "odoo_api"
 * )
 */
class OdooApi extends SourcePluginBase implements ContainerFactoryPluginInterface, OdooCronMigrationSourceInterface, OdooHighwaterSourceInterface {

  /**
   * Cron mode setting.
   *
   * @var bool
   *   Cron mode setting.
   *
   * @TODO: Move Cron mode and everything related to a subclass.
   */
  protected $cronMode = FALSE;

  /**
   * Cache for fields().
   *
   * @var array
   *   Migrate fields cache.
   *
   * @see fields()
   */
  protected $fields;

  /**
   * Cache for getOdooDatetimeFields()
   *
   * @var array
   *   Datetime fields list cache.
   *
   * @see getOdooDatetimeFields()
   */
  protected $odooDatetimeFields;

  /**
   * API client storage.
   *
   * @var \Drupal\odoo_api\OdooApi\ClientInterface
   *   API client object.
   */
  protected $apiClient;

  /**
   * Te model fields resolver.
   *
   * @var \Drupal\odoo_api\OdooApi\Data\ModelFieldsResolverInterface
   */
  protected $modelFieldsResolver;

  /**
   * Array or IDs of objects to force import on Cron.
   *
   * @var array
   */
  protected $forceCronImportIds = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, ClientInterface $odoo_client, ModelFieldsResolverInterface $model_fields_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->apiClient = $odoo_client;
    $this->modelFieldsResolver = $model_fields_resolver;

    // Allow tracking changes.
    // The variable value is set in overridden constructor since the original
    // source plugin base does not allow using both highwater and hashes at same
    // time.
    $this->trackChanges = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('odoo_api.api_client'),
      $container->get('odoo_api.model_fields_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      // Odoo models always has ID field.
      'id' => [
        'type' => 'integer',
        'size' => 'big',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    if (!isset($this->fields)) {
      $this->fields = [];
      foreach ($this->modelFieldsResolver->getModelFieldsData($this->getOdooRequestModelName()) as $field_name => $field) {
        $this->fields[$field_name] = $field['string'];
      }
    }

    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function setCronMode($value) {
    $this->cronMode = $value;
  }

  /**
   * {@inheritdoc}
   */
  public function forceCronImportObjects($ids) {
    $this->forceCronImportIds += array_combine($ids, $ids);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $offset = 0;
    $page_size = 50;

    $filter = $this->getOdooRequestFilter();
    while (TRUE) {
      $results = $this->apiClient->searchRead($this->getOdooRequestModelName(), $filter, $this->getOdooRequestFields(), $offset, $page_size, $this->getOdooRequestOrder());
      if (empty($results)) {
        // No more results.
        break;
      }

      $this->processJoins($results);

      foreach ($results as $row) {
        $this->convertDatetimeFields($row);
        yield $row;
      }
      if (count($results) < $page_size) {
        // That was the last page.
        return;
      }
      $offset += $page_size;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doCount() {
    return $this->apiClient->count($this->getOdooRequestModelName(), $this->getOdooRequestFilter());
  }

  /**
   * Provides Odoo model name, e.g. 'res.product'.
   *
   * @return string
   *   Odoo ERP model name.
   */
  protected function getOdooRequestModelName() {
    if (!isset($this->configuration['model'])) {
      throw new RequirementsException('Missing Odoo model setting');
    }

    return $this->configuration['model'];
  }

  /**
   * Provides Odoo filters. Falls back to empty filter if not supplied.
   *
   * This method may add additional filter on highwater property if the cron
   * mode is enabled.
   *
   * @return array
   *   Odoo API request filters array.
   */
  protected function getOdooRequestFilter() {
    // No filters by default.
    $filters = [];

    if (!empty($this->configuration['filters'])) {
      $filters = $this->configuration['filters'];
    }

    if ($this->cronMode) {
      // Add highwater field filter.
      if ($this->getHighWaterProperty()) {
        $high_water_field = $this->getHighWaterField();
        $high_water = $this->getOdooHighWaterValue();
        if ($high_water !== NULL) {
          if ($this->isOdooDatetimeField($this->getHighWaterField())) {
            $filters[] = [$high_water_field, '>=', $high_water];

            // *Only* import records older than 15 seconds from request time.
            // Sometimes Odoo could set the write_date of the record while still
            // processing related objects in background; in this case, the data
            // retrieved is not consistent. It's better to wait while things
            // settles and import something later than import incomplete
            // objects.
            $safe_import_time = DrupalDateTime::createFromTimestamp(\Drupal::time()->getRequestTime() - 15)
              ->setTimezone(new DateTimeZone('UTC'))
              ->format(ClientInterface::ODOO_DATETIME_FORMAT);
            $filters[] = [$high_water_field, '<=', $safe_import_time];
          }
          else {
            $filters[] = [$high_water_field, '>', $high_water];
          }
        }
      }

      // Add IDs which are forced.
      if (!empty($this->forceCronImportIds)) {
        // Polish notation. Do you like Polish notation?
        // The expression 'A OR (B AND C AND D)' becomes:
        // OR A AND AND B C D.
        $original_filter = $filters;
        $filters = [
          '|',
          ['id', 'in', array_values($this->forceCronImportIds)],
        ];

        // Checks if there is an operator (like "|") in the list of filters from
        // migration yml file.
        $already_existing_operators = 0;
        foreach ($original_filter as $original_filter_element) {
          if (is_string($original_filter_element)) {
            $already_existing_operators++;
          }
        }

        $operators_to_add = count($original_filter) - 1 - ($already_existing_operators * 2);
        for ($i = 0; $i < $operators_to_add; $i++) {
          $filters[] = '&';
        }
        foreach ($original_filter as $filter) {
          $filters[] = $filter;
        }
      }
    }

    return $filters;
  }

  /**
   * Get Odoo search_read request order.
   */
  protected function getOdooRequestOrder() {
    if ($this->cronMode) {
      // Sort by highwater field.
      if ($this->getHighWaterProperty()) {
        $high_water_field = $this->getHighWaterField();
        $high_water = $this->getOdooHighWaterValue();
        if ($high_water !== NULL) {
          return $high_water_field . ' asc';
        }
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function aboveHighwater(Row $row) {
    if ($this->cronMode
      && $this->isOdooDatetimeField($this->getHighWaterField())
      && $row->getSourceProperty($this->highWaterProperty['name']) == $this->originalHighWater) {
      // Check the row hash if Odoo write date is the same as highwater.
      // This behavior differs from original from source plugin base since we're
      // offloading highwater/filtering to Odoo API while in Cron mode.
      // We're always fetching objects where write date is greater *or equal*
      // than current highwater value, so we need additional check to avoid
      // updating same entities over and over again and again.
      $rowChanged = $this->rowChanged($row);
      return $rowChanged;
    }
    else {
      $aboveHighwater = parent::aboveHighwater($row);
      return $aboveHighwater;
    }
  }

  /**
   * Provides list of fields to fetch from Odoo.
   *
   * @return array|null
   *   Odoo API request fields list or NULL.
   *
   * @throws \Drupal\migrate\MigrateException
   *
   * @see \Drupal\odoo_api_migrate\Plugin\migrate\source\OdooApi::ensureJoinFields()
   */
  protected function getOdooRequestFields() {
    $fields = isset($this->configuration['fields']) ? $this->configuration['fields'] : [];
    $this->ensureJoinFields($fields);

    if (empty($fields)) {
      // No fields by default.
      return [];
    }

    // ID is always required by Migrate.
    $this->ensureField($fields, 'id');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getOdooHighWaterValue() {
    if (($highwater_value = $this->getHighWater()) === NULL) {
      // Do not try converting NULL value.
      return NULL;
    }

    if ($this->isOdooDatetimeField($this->getHighWaterField())) {
      return DrupalDateTime::createFromTimestamp($highwater_value)
        ->setTimezone(new DateTimeZone('UTC'))
        ->format(ClientInterface::ODOO_DATETIME_FORMAT);
    }

    return $highwater_value;
  }

  /**
   * Check if given if is datetime.
   *
   * @param string $field_name
   *   Odoo field name.
   *
   * @return bool
   *   Whether given field is datetime.
   */
  protected function isOdooDatetimeField($field_name) {
    $odoo_fields = $this->modelFieldsResolver->getModelFieldsData($this->getOdooRequestModelName());
    return !empty($odoo_fields[$field_name]['type'])&& $odoo_fields[$field_name]['type'] == 'datetime';
  }

  /**
   * Get list of Odoo datetime fields.
   *
   * @return array
   *   List of Odoo datetime fields.
   */
  protected function getOdooDatetimeFields() {
    if (!isset($this->odooDatetimeFields)) {
      $this->odooDatetimeFields = [];
      foreach ($this->fields() as $field_name => $description) {
        if ($this->isOdooDatetimeField($field_name)) {
          $this->odooDatetimeFields[] = $field_name;
        }
      }
    }

    return $this->odooDatetimeFields;
  }

  /**
   * Convert all Odoo datetime fields to timestamps.
   *
   * @param array $row
   *   Odoo API call result row.
   */
  protected function convertDatetimeFields(array &$row) {
    // Convert Datetime fields to timestamps.
    foreach ($this->getOdooDatetimeFields() as $timestamp_field) {
      if (isset($row[$timestamp_field])) {
        try {
          $row[$timestamp_field] = DrupalDateTime::createFromFormat(ClientInterface::ODOO_DATETIME_FORMAT, $row[$timestamp_field], 'UTC')
            ->getTimestamp();
        }
        catch (\UnexpectedValueException $e) {
          // Parsing failed.
          $row[$timestamp_field] = NULL;
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function saveHighWater($high_water) {
    if ($high_water < $this->getHighWater()) {
      return;
    }
    parent::saveHighWater($high_water);
  }

  /**
   * Ensures that the base join field is listed in the fields array.
   *
   * @param array $fields
   *   The fields to fetch array.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Throw the Migrate Exception if there is no base field.
   */
  protected function ensureJoinFields(array &$fields) {
    if (!empty($this->configuration['join'])) {
      foreach ($this->configuration['join'] as $join) {
        if (empty($join['base_field'])) {
          throw new MigrateException('Missing the base field to join another Odoo model.');
        }
        $this->ensureField($fields, $join['base_field']);
      }
    }
  }

  /**
   * Join other entities to the result.
   *
   * @param array $results
   *   The results array.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Throw the MigrateException if there is no model name.
   */
  protected function processJoins(array &$results) {
    if (empty($this->configuration['join'])) {
      return;
    }

    foreach ($this->configuration['join'] as $join) {
      if (empty($join['target_model'])) {
        throw new MigrateException('Missing join target model');
      }

      $ids = [];
      foreach ($results as $result) {
        if (!empty($result[$join['base_field']])) {
          $field_type = $this->modelFieldsResolver->getFieldType($join['base_field'], $this->getOdooRequestModelName());

          if ($field_type == 'many2one') {
            $related_model_ids = [reset($result[$join['base_field']])];
          }
          elseif (in_array($field_type, ['one2many', 'many2many'])) {
            $related_model_ids = $result[$join['base_field']];
          }
          else {
            throw new MigrateException('The model ' . $join['target_model'] . ' does not has the field ' . $join['base_field'] . ' or the field is not one2many or many2one type.');
          }

          $ids = array_merge($ids, $related_model_ids);
        }
      }

      $join_models = $this->loadJoinModels($join['target_model'], $ids, $join['fields']);

      foreach ($results as $key => $result) {
        if (!empty($result[$join['base_field']])) {
          $base_field_ids = $result[$join['base_field']];
          $result[$join['base_field']] = [];
          $join_ids = array_intersect($base_field_ids, array_keys($join_models));

          foreach ($join_ids as $id) {
            $result[$join['base_field']][$id] = $join_models[$id];
          }
        }

        $results[$key] = $result;
      }
    }
  }

  /**
   * Loads additional models to join.
   *
   * @param string $model
   *   The model name.
   * @param array $ids
   *   An array of IDs to load.
   * @param array $fields
   *   An array of fields to fetch.
   *
   * @return array
   *   The array of Odoo entities or empty array otherwise.
   */
  protected function loadJoinModels($model, array $ids, array $fields) {
    $this->ensureField($fields, 'id');

    if ($ids) {
      foreach ($this->apiClient->read($model, $ids, $fields) as $row) {
        $models[$row['id']] = $row;
      }
    }

    return isset($models) ? $models : [];
  }

  /**
   * Makes sure that the field is specified in the fields array.
   *
   * @param array $fields
   *   The fields array.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The fields array.
   */
  protected function ensureField(array &$fields, $field_name) {
    if (!in_array($field_name, $fields)) {
      $fields[] = $field_name;
    }
    return $fields;
  }

}
