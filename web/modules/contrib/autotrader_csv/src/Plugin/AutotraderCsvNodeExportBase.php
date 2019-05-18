<?php

namespace Drupal\autotrader_csv\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\node\NodeInterface;

/**
 * Base class for Autotrader CSV Node Export plugins.
 */
abstract class AutotraderCsvNodeExportBase extends PluginBase implements AutotraderCsvNodeExportInterface {

  /**
   * Constant for default field value.
   */
  protected const DEFAULT_FIELD_VALUE = "This is a field default value, please change me.";

  /**
   * The columns as defined for the CSV export.
   *
   * @var array
   */
  public $csvColumns = [
    'year',
    'make',
    'model',
    'trim',
    'sku',
    'vin',
    'price',
    'category_id',
    'last_modified',
    'photo',
    'photo_last_modified',
    'additional_photos',
    'additional_photos_last_modified',
  ];

  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node we're exporting.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $node;

  /**
   * List of fields.
   *
   * @var array
   */
  public $fields = [];

  /**
   * Creates a AutotraderCsvNodeExportBase instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates a AutotraderCsvNodeExportBase instance.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The Drupal global container.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   The object that is created using dependency injection.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\Core\Config\ConfigFactoryInterface $config_factory */
    $config_factory = $container->get('config.factory');
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $container->get('entity_type.manager');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $config_factory,
      $entity_type_manager
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setNode(NodeInterface $node) {
    $this->node = $node;
  }

  /**
   * {@inheritdoc}
   */
  public function toArray() {
    $columns = [];
    try {
      $file_storage = $this->entityTypeManager->getStorage('file');
      $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
      foreach ($this->fields as $csv_field => $field) {
        if ($field['value'] === self::DEFAULT_FIELD_VALUE) {
          if ($field['include_multi'] === FALSE) {
            $field_value = $this->node->get($field['field_name'])->getValue();
            if (!empty($field_value)) {
              $field_data = $field_value[$field['delta_start']];
              if ($field['is_term']) {
                $entity = $term_storage->load($field_data[$field['value_key']]);
                if (!empty($field['use_field_on_term'])) {
                  $this->fields[$csv_field]['value'] = $entity->get($field['use_field_on_term'])->getValue()[0]['value'];
                }
                else {
                  $this->fields[$csv_field]['value'] = $entity->label();
                }
              }
              elseif ($field['is_file']) {
                $entity = $file_storage->load($field_data[$field['value_key']]);
                $this->fields[$csv_field]['value'] = $entity->url();
              }
              elseif ($field['is_timestamp']) {
                $timestamp = $field_data[$field['value_key']];
                $this->fields[$csv_field]['value'] = date('m/d/Y', $timestamp);
              }
              else {
                $this->fields[$csv_field]['value'] = $field_data[$field['value_key']];
              }
            }
            // Field has no value.
            else {
              $this->fields[$csv_field]['value'] = "";
            }
          }
          else {
            $items = $this->node->get($field['field_name'])->getValue();
            $values = [];
            foreach ($items as $item_delta => $item) {
              if ($item_delta >= $field['delta_start']) {
                // Load an entity.
                if ($field['value_key'] == "target_id") {
                  // Entity is Term.
                  if ($field['is_term']) {
                    $entity = $term_storage->load($item['target_id']);
                    $values[] = $entity->label();
                  }
                  // Entity is something else.
                  else {
                    $entity = $file_storage->load($item['target_id']);
                    switch ($csv_field) {
                      // Entity is file and needs a timestamp.
                      case "additional_photos_last_modified":
                        $timestamp = $entity->get('changed')->getValue()[0]['value'];
                        $values[] = date('m/d/Y', $timestamp);
                        break;

                      default:
                        $values[] = $entity->url();
                    }
                  }
                }
                else {
                  $values[] = $item[$field['value_key']];
                }
              }
            }
            $this->fields[$csv_field]['value'] = $values;
          }
        }
        $columns[$csv_field] = $this->fields[$csv_field]['value'];
      }
    }
    catch (\Exception $exception) {
      watchdog_exception("autotrader_csv", $exception);
    }
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function toString() {
    $columns = $this->toArray();
    foreach ($columns as $delta_column => $column) {
      if (is_array($column)) {
        $columns[$delta_column] = implode(';', $column);
      }
    }
    return '"' . implode('","', $columns) . '"';
  }

  /**
   * Get string of column names.
   */
  public function csvColToString() {
    $columns = $this->csvColumns;
    return '"' . implode('","', $columns) . '"';
  }

}
