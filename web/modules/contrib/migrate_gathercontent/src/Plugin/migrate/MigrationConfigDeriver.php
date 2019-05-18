<?php

namespace Drupal\migrate_gathercontent\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate_gathercontent\DrupalGatherContentClient;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Deriver GatherContent configurations.
 */
class MigrationConfigDeriver extends DeriverBase implements ContainerDeriverInterface {
  use MigrationDeriverTrait;

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The GatherContent client
   *
   * @var \Drupal\migrate_gathercontent\DrupalGatherContentClient
   */
  protected $client;

  /**
   * Entity Type Manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Selection Plugin Manager
   *
   * @var \Drupal\core\Entity\EntityReferenceSelection\SelectionPluginManager
   */
  protected $selectionPluginManager;

  /**
   * Migration Plugin Manager
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * List of destination exceptions.
   *
   * // TODO: This should be managed with a plugin.
   * @var array
   */
  protected $destination_exceptions = [
    'paragraph' => 'entity_reference_revisions:paragraph',
  ];

  /**
   * MigrationConfigDeriver constructor.
   * @param $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *  The EntityTypeManager.
   * @param \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager $selectionPluginManager
   *  The Selection plugin manager.
   * @param \Drupal\migrate_gathercontent\DrupalGatherContentClient $gathercontent_client
   *  The GatherContentClient.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *  The MigrationPluginManager.
   */
  public function __construct($base_plugin_id,  EntityTypeManager $entityTypeManager, SelectionPluginManager $selectionPluginManager, DrupalGatherContentClient $gathercontent_client, MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->basePluginId = $base_plugin_id;
    $this->client = $gathercontent_client;
    $this->selectionPluginManager = $selectionPluginManager;
    $this->entityTypeManager = $entityTypeManager;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    // Translations don't make sense unless we have content_translation.
    return new static(
      $base_plugin_id,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.entity_reference_selection'),
      $container->get('migrate_gathercontent.client'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Only load mappings that are enabled.
    $mapping_entities = $this->entityTypeManager->getStorage('gathercontent_mapping')->loadByProperties([
      'status' => TRUE,
    ]);

    if (!empty($mapping_entities)) {
      foreach ($mapping_entities as $entity) {

        $definition = $base_plugin_definition;

        // Migration dependencies.
        $dependencies = [];

        // Load field definitions.
        $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->get('entity_type'), $entity->get('bundle'));

        // If no field mappings are set then don't define a migration.
        $field_mappings = $entity->getFieldMappings();
        if (!empty($field_mappings)) {
          unset($definition['deriver']);

          $definition['id'] = $entity->id();
          $definition['label'] = $entity->label();
          $definition['source']['project_id'] = $entity->get('project_id');
          $definition['source']['template'] = $entity->get('template');

          // Setting the bundle key.
          // This is necessary for supporting different entity bundles like
          // nodes, terms etc.
          $bundle_key = $this->entityTypeManager->getDefinition($entity->get('entity_type'))->getKey('bundle');
          $definition['process'][$bundle_key] = [
              'plugin' => 'default_value',
              'default_value' => $entity->get('bundle'),
          ];

          // Doing some tasks before setting up process plugins.
          foreach ($field_mappings as $source => $field_info) {
            // Creating compound sources.
            // Handling when a field is mapped to two or more sources.
            // We combine the values from those sources into one source and
            // then it goes through the normal process pipeline.
            if (!empty($field_info['field'])) {
              $field = $field_info['field'];
              if ($this->hasMultipleSources($field, $entity)) {
                $definition['process'] += $this->createCompoundSource($field, $entity);
              }
            }

            // Add required migration dependencies. Note this will be
            // reversed because the order of the migrations matters.
            if ($source_mapping = $this->loadMapping($source)) {
              $dependencies['required'][] = $source_mapping->getMigrationId();
            }
          }

          // Begin process pipeline.
          // TODO: Need to abstract this out into plugins?
          foreach ($field_mappings as $source => $field_info) {
            if (!empty($field_info['field'])) {
              $field = $field_info['field'];

              // Checking for compound sources.
              // If there are any use that instead.
              if ($this->hasMultipleSources($field, $entity)) {
                $source = '@_compound_' . $field;
              }

              // Only add process plugin
              if (!empty($field_definitions[$field]) && empty($definition['process'][$field])) {
                $type = $field_definitions[$field]->getType();

                // If the source is a migration then go through that pipeline.
                if ($this->loadMapping($source)) {
                  $field_process = $this->processMigrationPipeline($field, $source, $type, $entity);
                }
                // Otherwise go through normal field pipeline.
                else {
                  $field_process = $this->processPipeline($field, $source, $type, $entity);
                }

                $definition['process'] = array_merge($definition['process'], $field_process);
              }
            }
          }

          // Set migration dependencies.
          if (!empty($dependencies)) {
            $definition['migration_dependencies'] = array_reverse($dependencies);
          }

          // Set destination.
          // Check to see if there are any exceptions.
          if (!empty($this->destination_exceptions[$entity->get('entity_type')])) {
            $destination = $this->destination_exceptions[$entity->get('entity_type')];
          }
          // Otherwise use default destination plugin.
          else {
            $destination = 'entity:' . $entity->get('entity_type');
          }

          $definition['destination'] = [
            'plugin' => $destination,
            'default_bundle' => $entity->get('bundle'),
          ];

          $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);
          $this->derivatives[$entity->id()] = $migration->getPluginDefinition();
        }
      }
    }

    return $this->derivatives;
  }

  /**
   * This combines values from multiple sources into a single source.
   *
   * @param $field_name
   * @param $entity
   * @return array
   */
  private function createCompoundSource($field_name, $entity) {
    // TODO: refactor this to make it more concise.
    $process = [];
    $multi_sources = [];
    $field_mappings = $entity->getFieldMappings();
    if ($this->hasMultipleSources($field_name, $entity)) {
      foreach($field_mappings as $key => $field_info) {
        if ($field_info['field'] == $field_name) {
          $multi_sources[] = $key;
        }
      }
    }

    // Creating prepared sources.
    if (!empty($multi_sources)) {
      $prepared_sources = [];
      foreach ($multi_sources as  $source) {
        // If the source is a migration then look up those values first.
        if ($source_mapping = $this->loadMapping($source)) {
          $process['_prepare_' . $source][] = [
            'plugin' => 'migration_lookup',
            'migration' => $source_mapping->getMigrationId(),
            'source' => 'id',
          ];
        }
        // Otherwise use normal 'get' plugin.
        else {
          $process['_prepare_' . $source][] = [
            'plugin' => 'get',
            'source' => $source,
          ];
        }

        $prepared_sources[] = '@_prepare_' . $source;
      }

      // Creating the compound field, we refer to this field as the source.
      // e.g $source = '@_compound_' . $field_name.
      $process['_compound_' . $field_name][] = [
        'plugin' => 'get',
        'source' => $prepared_sources,
      ];

      // Only arrays from fields should be flattened.
      if (!$this->loadMapping($source)) {
        $process['_compound_' . $field_name][] = [
          'plugin' => 'flatten',
        ];
      }
    }

    return $process;
  }

  /**
   * Helper function for determining if a field has multiple sources defined.
   *
   * @param $field_name
   * @param $entity
   * @return bool
   */
  private function hasMultipleSources($field_name, $entity) {

    $field_mappings = $entity->getFieldMappings();
    foreach ($field_mappings as $id => $value) {
      $values[$id] = $value['field'];
    }
    $value_count = array_count_values($values);
    if (isset($value_count[$field_name])) {
      return ($value_count[$field_name] > 1);
    }
    else {
      return FALSE;
    }
  }

  /**
   * Helper function to determine if mapping is a migration or field.
   *
   * @param string $source
   * @return bool
   */
  private function loadMapping($source) {
    $entity = $this->entityTypeManager->getStorage('gathercontent_mapping')->load($source);
    if (!empty($entity)) {
      return $entity;
    }

    return FALSE;
  }

  /**
   * Helper function for creating process plugins for migration sources.
   *
   * @param string $field_name
   * @param string $migration
   * @param string $type
   * @param object $entity
   * @return array
   */
  private function processMigrationPipeline($field_name, $source, $type, $entity) {

    $mapping = $this->loadMapping($source);

    $process = [];
    switch ($type) {

      // Entity reference fields.
      case 'entity_reference':

        $process[$field_name][] = [
          'plugin' => 'deepen',
          'source' => 'id',
          'keyname' => 'value',
        ];
        $process[$field_name][] = [
          'plugin' => 'sub_process',
          'process' => [
            'target_id' => [
              'plugin' => 'migration_lookup',
              'migration' => $mapping->getMigrationId(),
              'source' => 'value',
            ],
          ]
        ];
        break;

      // Entity Reference Revisions.
      case 'entity_reference_revisions':

        $process[$field_name][] = [
          'plugin' => 'deepen',
          'source' => 'id',
          'keyname' => 'value',
        ];
        $process[$field_name][] = [
          'plugin' => 'sub_process',
          'process' => [
            'temporary_id' => [
              'plugin' => 'migration_lookup',
              'migration' => $mapping->getMigrationId(),
              'source' => 'value',
            ],
            'target_id' => [
              'plugin' => 'extract',
              'source' => '@temporary_id',
              'index' => [0],
            ],
            'target_revision_id' => [
              'plugin' => 'extract',
              'source' => '@temporary_id',
              'index' => [1],
            ]
          ]
        ];

        break;
    }
    return $process;
  }

  /**
   * Helper function for creating the process plugins.
   *
   * @param $field_name
   * @param $source
   * @param $type
   * @param $entity
   * @return array
   */
  private function processPipeline($field_name, $source, $type, $entity) {

    $process = [];
    switch($type) {

      // Entity reference revisions.
      // Note: This only executes for fields with multiple sources.
      // Otherwise is uses self::processMigrationPipeline.
      case 'entity_reference_revisions':

        $process[$field_name][] = [
          'plugin' => 'deepen',
          'source' => $source,
          'keyname' => 'value',
        ];
        $process[$field_name][] = [
          'plugin' => 'sub_process',
          'process' => [
              'target_id' => [
                'source' => 'value',
                'plugin' => 'extract',
                'index' => [0],
              ],
              'target_revision_id' => [
                'source' => 'value',
                'plugin' => 'extract',
                'index' => [1],
              ]
            ]
          ];

        break;

      // Entity reference fields.
      case 'entity_reference':
        $field_definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions($entity->get('entity_type'), $entity->get('bundle'));
        $lookupInformation = $this->getLookupInformation($field_definitions[$field_name]);

        $process[$field_name][] = [
          'plugin' => 'deepen',
          'source' => $source,
          'keyname' => 'value',
        ];
        $process[$field_name][] = [
          'plugin' => 'sub_process',
          'process' => [
            'target_id' => [
              'plugin' => 'gathercontent_entity_lookup',
              'source' => 'value',
              'destination_field' => $field_name,
              'value_key' => $lookupInformation['value_key'],
              'entity_type' => $lookupInformation['entity_type'],
              'bundle_key' => $lookupInformation['bundle_key'],
              'bundle' => $lookupInformation['bundle'],
            ]
          ]
        ];
        break;

      // Images
      case 'image':
        $process[$field_name][] = [
          'plugin' => 'sub_process',
          'source' => $source,
          'process' => [
            'target_id' => [
              'plugin' => 'gathercontent_file_import',
              'id_only' => TRUE,
              'destination_field' => $field_name,
              'source' => [
                'url',
                'filename',
              ],
            ],
            'title' => 'filename',
            'alt' => 'filename',
          ]
        ];

        break;

      // Files
      case 'file':
        $process[$field_name][] = [
          'plugin' => 'sub_process',
          'source' => $source,
          'process' => [
            'target_id' => [
              'plugin' => 'gathercontent_file_import',
              'id_only' => TRUE,
              'destination_field' => $field_name,
              'source' => [
                'url',
                'filename',
              ],
            ],
            'title' => 'filename',
          ]
        ];

        break;

      // Timestamp
      case 'timestamp':
        // TODO: Need to make this more flexible.
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'strip_tags',
          'source' => $source,
        ];
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'trim',
        ];
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'strtotime',
        ];
        break;

      // Datetime fields.
      case 'datetime':
        // TODO: Need to make this more flexible.
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'strip_tags',
          'source' => $source,
        ];
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'trim',
        ];
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'strtotime',
        ];
        $definition[$field_name][] = [
          'plugin' => 'format_date',
          'from_format' => 'U',
          'to_format' => DateTimeItemInterface::DATETIME_STORAGE_FORMAT
        ];
        break;

      // Rich text fields.
      case 'text':
      case 'text_with_summary':
        $formats = filter_formats();
        $names = array_keys($formats);
        $format = $names[0];

        $process[$field_name . '/value'][] = [
          'plugin' => 'get',
          'source' => $source,
        ];
        $process[$field_name . '/format'][] = [
          'plugin' => 'default_value',
          'default_value' => $format,
        ];

        break;

      // Plain text fields.
      case 'string':
      case 'string_long':
        // Note: Callable does not accept an array of callbacks.
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'strip_tags',
          'source' => $source,
        ];
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'trim',
        ];
        break;

      // List strings.
      case 'list_string':
        // List fields do not work with deltas on multi value fields.
        $process[$field_name][] = [
          'plugin' => 'get',
          'source' => $source,
        ];
        break;

      // Email Address
      case 'email':
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'strip_tags',
          'source' => $source,
        ];
        $process[$field_name][] = [
          'plugin' => 'callback',
          'callable' => 'trim',
        ];
        break;

      // Link
      case 'link':
        // TODO: Add support for link text.
        $process[$field_name . '/uri'][] = [
          'plugin' => 'callback',
          'callable' => 'strip_tags',
          'source' => $source,
        ];
        $process[$field_name . '/uri'][] = [
          'plugin' => 'callback',
          'callable' => 'trim',
        ];
        break;

      default:
        $process[$field_name][] = [
          'plugin' => 'get',
          'source' => $source,
        ];
        break;
    }

    return $process;
  }

  /**
   * Helper function for fetching field information for entity_lookup.
   *
   * @param $field_config
   *   Field config information
   *
   * @return array
   *   Lookup information
   */
  private function getLookupInformation($field_config) {
    $handlerSettings = $field_config->getSetting('handler_settings');
    $bundles = array_filter((array) $handlerSettings['target_bundles']);
    if (count($bundles) == 1) {
      $lookupBundle = reset($bundles);
    }
    // This was added in 8.1.x is not supported in 8.0.x.
    elseif (!empty($handlerSettings['auto_create']) && !empty($handlerSettings['auto_create_bundle'])) {
      $lookupBundle = reset($handlerSettings['auto_create_bundle']);
    }
    else {
      $lookupBundle = array_values($bundles);
    }

    // Make an assumption that if the selection handler can target more
    // than one type of entity that we will use the first entity type.
    $lookupEntityType = reset($this->selectionPluginManager->createInstance($field_config->getSetting('handler'))->getPluginDefinition()['entity_types']);
    $lookupValueKey = $this->entityTypeManager->getDefinition($lookupEntityType)->getKey('label');
    $lookupBundleKey = $this->entityTypeManager->getDefinition($lookupEntityType)->getKey('bundle');

    // Note in some cases $lookupBundle will have an array of values.
    $info = [
      'value_key' => $lookupValueKey,
      'entity_type' => $lookupEntityType,
      'bundle_key' => $lookupBundleKey,
      'bundle' => $lookupBundle,
    ];

    return $info;
  }
}
