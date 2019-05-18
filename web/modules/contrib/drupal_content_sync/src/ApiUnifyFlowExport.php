<?php

namespace Drupal\drupal_content_sync;

use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\Entity\Pool;
use Drupal\encrypt\Entity\EncryptionProfile;
use Drupal\user\Entity\User;
use Drupal\drupal_content_sync\Form\PoolForm;

/**
 * Class ApiUnifyConfig used to export the Synchronization config to the API
 * Unify backend.
 */
class ApiUnifyFlowExport extends ApiUnifyExport {
  /**
   * @var string PREVIEW_CONNECTION_ID
   *   The unique connection ID in API Unify used to store preview entities at.
   */
  const PREVIEW_CONNECTION_ID = 'drupal_drupal-content-sync_preview';
  /**
   * @var string PREVIEW_ENTITY_ID
   *   The entity type ID from API Unify used to store preview entities as.
   */
  const PREVIEW_ENTITY_ID = 'drupal-synchronization-entity_preview-0_1';
  /**
   * @var string PREVIEW_ENTITY_VERSION
   *   The preview entity version (see above).
   */
  const PREVIEW_ENTITY_VERSION = '0.1';

  /**
   * @var string READ_LIST_ENTITY_ID
   *   "ID" used to perform list requests in the
   *   {@see DrupalContentSyncEntityResource}. Should be refactored later.
   */
  const READ_LIST_ENTITY_ID = '0';

  /**
   * @var string DEPENDENCY_CONNECTION_ID
   *   The format for connection IDs. Must be used consequently to allow
   *   references to be resolved correctly.
   */
  const DEPENDENCY_CONNECTION_ID = 'drupal-[api.name]-[instance.id]-[entity_type.name_space]-[entity_type.name]-[entity_type.version]';
  /**
   * @var string POOL_DEPENDENCY_CONNECTION_ID
   *   Same as {@see Flow::DEPENDENCY_CONNECTION_ID} but for the
   *   pool connection.
   */
  const POOL_DEPENDENCY_CONNECTION_ID = 'drupal-[api.name]-' . ApiUnifyPoolExport::POOL_SITE_ID . '-[entity_type.name_space]-[entity_type.name]-[entity_type.version]';

  /**
   * @var \Drupal\drupal_content_sync\Entity\Flow
   */
  protected $flow;

  /**
   * ApiUnifyConfig constructor.
   *
   * @param \Drupal\drupal_content_sync\Entity\Flow $flow
   *   The flow this exporter is used for.
   */
  public function __construct(Flow $flow) {
    parent::__construct();

    $this->flow = $flow;
  }

  /**
   * Get the API Unify connection ID for the given entity type config.
   *
   * @param string $api_id
   *   API ID from this config.
   * @param string $site_id
   *   ID from this site from this config.
   * @param string $entity_type_name
   *   The entity type.
   * @param string $bundle_name
   *   The bundle.
   * @param string $version
   *   The version. {@see Flow::getEntityTypeVersion}.
   *
   * @return string A unique connection ID.
   */
  public static function getExternalConnectionId($api_id, $site_id, $entity_type_name, $bundle_name, $version) {
    return sprintf('drupal-%s-%s-%s-%s-%s',
      $api_id,
      $site_id,
      $entity_type_name,
      $bundle_name,
      $version
    );
  }

  /**
   * Get the API Unify entity type ID for the given entity type config.
   *
   * @param string $api_id
   *   API ID from this config.
   * @param string $entity_type_name
   *   The entity type.
   * @param string $bundle_name
   *   The bundle.
   * @param string $version
   *   The version. {@see Flow::getEntityTypeVersion}.
   *
   * @return string A unique entity type ID.
   */
  public static function getExternalEntityTypeId($api_id, $entity_type_name, $bundle_name, $version) {
    return sprintf('drupal-%s-%s-%s-%s',
      $api_id,
      $entity_type_name,
      $bundle_name,
      $version
    );
  }

  /**
   * Get the API Unify connection path for the given entity type config.
   *
   * @param string $api_id
   *   API ID from this config.
   * @param string $site_id
   *   ID from this site from this config.
   * @param string $entity_type_name
   *   The entity type.
   * @param string $bundle_name
   *   The bundle.
   * @param string $version
   *   The version. {@see Flow::getEntityTypeVersion}.
   *
   * @return string A unique connection path.
   */
  public static function getExternalConnectionPath($api_id, $site_id, $entity_type_name, $bundle_name, $version) {
    return sprintf('drupal/%s/%s/%s/%s/%s',
      $api_id,
      $site_id,
      $entity_type_name,
      $bundle_name,
      $version
    );
  }

  /**
   * Wrapper for {@see Flow::getInternalUrl} for the "create_item"
   * operation.
   *
   * @param $api_id
   * @param $entity_type_name
   * @param $bundle_name
   * @param $version
   *
   * @return string
   */
  public static function getInternalCreateItemUrl($api_id, $entity_type_name, $bundle_name, $version) {
    return ApiUnifyPoolExport::getInternalUrl($api_id, $entity_type_name, $bundle_name, $version);
  }

  /**
   * Wrapper for {@see Flow::getInternalUrl} for the "update_item"
   * operation.
   *
   * @param $api_id
   * @param $entity_type_name
   * @param $bundle_name
   * @param $version
   *
   * @return string
   */
  public static function getInternalUpdateItemUrl($api_id, $entity_type_name, $bundle_name, $version) {
    return ApiUnifyPoolExport::getInternalUrl($api_id, $entity_type_name, $bundle_name, $version, '[id]');
  }

  /**
   * Wrapper for {@see Flow::getInternalUrl} for the "delete_item"
   * operation.
   *
   * @param $api_id
   * @param $entity_type_name
   * @param $bundle_name
   * @param $version
   *
   * @return string
   */
  public static function getInternalDeleteItemUrl($api_id, $entity_type_name, $bundle_name, $version) {
    return ApiUnifyPoolExport::getInternalUrl($api_id, $entity_type_name, $bundle_name, $version, '[id]');
  }

  /**
   * Wrapper for {@see Flow::getInternalUrl} for the "read_list"
   * operation.
   *
   * @param $api_id
   * @param $entity_type_name
   * @param $bundle_name
   * @param $version
   *
   * @return string
   */
  public static function getInternalReadListUrl($api_id, $entity_type_name, $bundle_name, $version) {
    return ApiUnifyPoolExport::getInternalUrl($api_id, $entity_type_name, $bundle_name, $version, self::READ_LIST_ENTITY_ID);
  }

  /**
   * Create all entity types, connections and synchronizations as required.
   *
   * @throws \Exception If the user profile for import is not available.
   */
  public function prepareBatch() {
    $export_url = static::getBaseUrl();
    $enable_preview = static::isPreviewEnabled();

    $dcs_disable_optimization = boolval(\Drupal::config('drupal_content_sync.debug')
      ->get('dcs_disable_optimization'));

    $user = User::load(DRUPAL_CONTENT_SYNC_USER_ID);
    // During the installation from an existing config for some reason DRUPAL_CONTENT_SYNC_USER_ID is not set right after the installation of the module, so we've to double check that...
    // @ToDo: Why?
    if (is_null(DRUPAL_CONTENT_SYNC_USER_ID)) {
      $user = User::load(\Drupal::service('keyvalue.database')
        ->get('drupal_content_sync_user')
        ->get('uid'));
    }

    if (!$user) {
      throw new \Exception(
        t("Drupal Content Sync User not found. Encrypted data can't be saved")
      );
    }

    $userData = \Drupal::service('user.data');
    $loginData = $userData->get('drupal_content_sync', $user->id(), 'sync_data');

    if (!$loginData) {
      throw new \Exception(t("No credentials for sync user found."));
    }

    $encryption_profile = EncryptionProfile::load(DRUPAL_CONTENT_SYNC_PROFILE_NAME);

    foreach ($loginData as $key => $value) {
      $loginData[$key] = \Drupal::service('encryption')
        ->decrypt($value, $encryption_profile);
    }

    $entity_types = $this->flow->sync_entities;

    $pools = Pool::getAll();

    $this->remove(TRUE);

    $operations = [];

    foreach ($this->flow->getEntityTypeConfig() as $id => $type) {
      $entity_type_name = $type['entity_type_name'];
      $bundle_name      = $type['bundle_name'];
      $version          = $type['version'];

      if ($type['handler'] == Flow::HANDLER_IGNORE) {
        continue;
      }
      $handler = $this->flow->getEntityTypeHandler($type);

      $entityFieldManager = \Drupal::service('entity_field.manager');
      /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fields */
      $fields = $entityFieldManager->getFieldDefinitions($entity_type_name, $bundle_name);

      $entity_type_pools = [];
      foreach ($type['import_pools'] as $pool_id => $state) {
        if (!isset($entity_type_pools[$pool_id])) {
          $entity_type_pools[$pool_id] = [];
        }
        $entity_type_pools[$pool_id]['import'] = $state;
      }
      foreach ($type['export_pools'] as $pool_id => $state) {
        if (!isset($entity_type_pools[$pool_id])) {
          $entity_type_pools[$pool_id] = [];
        }
        $entity_type_pools[$pool_id]['export'] = $state;
      }

      foreach ($entity_type_pools as $pool_id => $definition) {
        $pool   = $pools[$pool_id];
        $export = $definition['export'];
        $import = $definition['import'];

        if ($export == Pool::POOL_USAGE_FORBID && $import == Pool::POOL_USAGE_FORBID) {
          continue;
        }

        $url     = $pool->getBackendUrl();
        $api     = $pool->id;
        $site_id = $pool->getSiteId();

        if (strlen($site_id) > PoolForm::siteIdMaxLength) {
          throw new \Exception(t('The site id of pool ' . $pool_id . ' is having more then ' . PoolForm::siteIdMaxLength . ' characters. This is not allowed due to backend limitations and will result in an exception when it is trying to be exported.'));
        }

        $entity_type_id = self::getExternalEntityTypeId($api, $entity_type_name, $bundle_name, $version);
        $entity_type = [
          'id' => $entity_type_id,
          'name_space' => $entity_type_name,
          'name' => $bundle_name,
          'version' => $version,
          'base_class' => "api-unify/services/drupal/v0.1/models/base.model",
          'custom' => TRUE,
          'new_properties' => [
            'source' => [
              'type' => 'reference',
              'default_value' => NULL,
              'connection_identifiers' => [
                [
                  'properties' => [
                    'id' => 'source_connection_id',
                  ],
                ],
              ],
              'model_identifiers' => [
                [
                  'properties' => [
                    'id' => 'source_id',
                  ],
                ],
              ],
              'multiple' => FALSE,
            ],
            'source_id' => [
              'type' => 'id',
              'default_value' => NULL,
            ],
            'source_connection_id' => [
              'type' => 'id',
              'default_value' => NULL,
            ],
            'preview' => [
              'type' => 'string',
              'default_value' => NULL,
            ],
            'url' => [
              'type' => 'string',
              'default_value' => NULL,
            ],
            'apiu_translation' => [
              'type' => 'object',
              'default_value' => NULL,
            ],
            'metadata' => [
              'type' => 'object',
              'default_value' => NULL,
            ],
            'embed_entities' => [
              'type' => 'object',
              'default_value' => NULL,
              'multiple' => TRUE,
            ],
            'title' => [
              'type' => 'string',
              'default_value' => NULL,
            ],
            'created' => [
              'type' => 'int',
              'default_value' => NULL,
            ],
            'changed' => [
              'type' => 'int',
              'default_value' => NULL,
            ],
            'uuid' => [
              'type' => 'string',
              'default_value' => NULL,
            ],
          ],
          'new_property_lists' => [
            'list' => [
              '_resource_url' => 'value',
              '_resource_connection_id' => 'value',
              'id' => 'value',
            ],
            'reference' => [
              '_resource_url' => 'value',
              '_resource_connection_id' => 'value',
              'id' => 'value',
            ],
            'details' => [
              '_resource_url' => 'value',
              '_resource_connection_id' => 'value',
              'id' => 'value',
              'source' => 'reference',
              'apiu_translation' => 'value',
              'metadata' => 'value',
              'embed_entities' => 'value',
              'title' => 'value',
              'created' => 'value',
              'changed' => 'value',
              'uuid' => 'value',
              'url' => 'value',
            ],
            'database' => [
              'id' => 'value',
              'source_id' => 'value',
              'source_connection_id' => 'value',
              'preview' => 'value',
              'url' => 'value',
              'apiu_translation' => 'value',
              'metadata' => 'value',
              'embed_entities' => 'value',
              'title' => 'value',
              'created' => 'value',
              'changed' => 'value',
              'uuid' => 'value',
            ],
            'modifiable' => [
              'title' => 'value',
              'preview' => 'value',
              'url' => 'value',
              'apiu_translation' => 'value',
              'metadata' => 'value',
              'embed_entities' => 'value',
            ],
            'required' => [
              'uuid' => 'value',
            ],
          ],
          'api_id' => $api . '-' . ApiUnifyPoolExport::CUSTOM_API_VERSION,
        ];

        $forbidden = $handler->getForbiddenFields();

        foreach ($fields as $key => $field) {
          if (!isset($entity_types[$id . '-' . $key])) {
            continue;
          }

          if (in_array($key, $forbidden)) {
            continue;
          }

          if (isset($entity_type['new_properties'][$key])) {
            continue;
          }

          $entity_type['new_properties'][$key] = [
            'type' => 'object',
            'default_value' => NULL,
            'multiple' => TRUE,
          ];

          $entity_type['new_property_lists']['details'][$key] = 'value';
          $entity_type['new_property_lists']['database'][$key] = 'value';

          $entityFieldManager = \Drupal::service('entity_field.manager');
          $field_definition = $entityFieldManager->getFieldDefinitions($entity_type_name, $bundle_name)[$key];

          if ($field_definition->isRequired()) {
            $entity_type['new_property_lists']['required'][$key] = 'value';
          }

          if (!$field_definition->isReadOnly()) {
            $entity_type['new_property_lists']['modifiable'][$key] = 'value';
          }
        }

        // TODO entity types should also contain the entity type handler in their machine name, preventing the following potential errors:
        // - Different flows may use different entity type handlers, resulting in different entity type definitions for the same entity type
        // - Changing the entity type handler must change the entity type definition which will not work if we don't update the machine name.
        $handler->updateEntityTypeDefinition($entity_type);

        // Create the entity type.
        $operations[] = [$url . '/api_unify-api_unify-entity_type-0_1', [
          'json' => $entity_type,
        ],
        ];

        $pool_connection_id = self::getExternalConnectionId($api, ApiUnifyPoolExport::POOL_SITE_ID, $entity_type_name, $bundle_name, $version);
        // Create the pool connection entity for this entity type.
        $operations[] = [$url . '/api_unify-api_unify-connection-0_1', [
          'json' => [
            'id' => $pool_connection_id,
            'name' => 'Drupal pool connection for ' . $entity_type_name . '-' . $bundle_name . '-' . $version,
            'hash' => self::getExternalConnectionPath($api, ApiUnifyPoolExport::POOL_SITE_ID, $entity_type_name, $bundle_name, $version),
            'usage' => 'EXTERNAL',
            'status' => 'READY',
            'options' => [
              'update_all' => $dcs_disable_optimization,
            ],
            'entity_type_id' => $entity_type_id,
          ],
        ],
        ];

        // Create a synchronization from the pool to the preview connection.
        if ($enable_preview) {
          $operations[] = [$url . '/api_unify-api_unify-connection_synchronisation-0_1', [
            'json' => [
              'id' => $pool_connection_id . '--to--preview',
              'name' => 'Synchronization Pool ' . $entity_type_name . '-' . $bundle_name . ' -> Preview',
              'options' => [
                'create_entities' => TRUE,
                'update_entities' => TRUE,
                'delete_entities' => TRUE,
                'update_none_when_loading' => TRUE,
                'exclude_reference_properties' => [
                  'pSource',
                ],
              ],
              'status' => 'READY',
              'source_connection_id' => $pool_connection_id,
              'destination_connection_id' => self::PREVIEW_CONNECTION_ID,
            ],
          ],
          ];
        }

        $crud_operations = [
          'create_item' => [
            'url' => self::getInternalCreateItemUrl($api, $entity_type_name, $bundle_name, $version),
          ],
          'update_item' => [
            'url' => self::getInternalUpdateItemUrl($api, $entity_type_name, $bundle_name, $version),
          ],
          'delete_item' => [
            'url' => self::getInternalDeleteItemUrl($api, $entity_type_name, $bundle_name, $version),
          ],
        ];
        $connection_options = [
          'authentication' => [
            'type' => 'drupal8_services',
            'username' => $loginData['userName'],
            'password' => $loginData['userPass'],
            'base_url' => $export_url,
          ],
          'update_all' => $dcs_disable_optimization,
          'crud' => $crud_operations,
        ];

        if ($export != Pool::POOL_USAGE_FORBID && $type['export'] == ExportIntent::EXPORT_AUTOMATICALLY) {
          $crud_operations['read_list']['url'] = self::getInternalReadListUrl($api, $entity_type_name, $bundle_name, $version);
        }

        $local_connection_id = self::getExternalConnectionId($api, $site_id, $entity_type_name, $bundle_name, $version);
        // Create the instance connection entity for this entity type.
        $operations[] = [$url . '/api_unify-api_unify-connection-0_1', [
          'json' => [
            'id' => $local_connection_id,
            'name' => 'Drupal connection on ' . $site_id . ' for ' . $entity_type_name . '-' . $bundle_name . '-' . $version,
            'hash' => self::getExternalConnectionPath($api, $site_id, $entity_type_name, $bundle_name, $version),
            'usage' => 'EXTERNAL',
            'status' => 'READY',
            'entity_type_id' => $entity_type_id,
            'instance_id' => $site_id,
            'options' => $connection_options,
          ],
        ],
        ];

        // Create a synchronization from the pool to the local connection.
        if ($import != Pool::POOL_USAGE_FORBID && $type['import'] != ImportIntent::IMPORT_DISABLED) {
          $operations[] = [$url . '/api_unify-api_unify-connection_synchronisation-0_1', [
            'json' => [
              'id' => $local_connection_id . '--to--drupal',
              'name' => 'Synchronization for ' . $entity_type_name . '/' . $bundle_name . '/' . $version . ' from Pool -> ' . $site_id,
              'options' => [
                'dependency_connection_id' => self::DEPENDENCY_CONNECTION_ID,
                'create_entities' => $type['import'] != ImportIntent::IMPORT_MANUALLY,
                'force_updates' => $dcs_disable_optimization,
                'update_entities' => TRUE,
                'delete_entities' => boolval($type['import_deletion_settings']['import_deletion']),
                'dependent_entities_only' => $type['import'] == ImportIntent::IMPORT_AS_DEPENDENCY,
                'update_none_when_loading' => TRUE,
                'exclude_reference_properties' => [
                  'pSource',
                ],
              ],
              'status' => 'READY',
              'source_connection_id' => $pool_connection_id,
              'destination_connection_id' => $local_connection_id,
            ],
          ],
          ];
        }
        if ($export != Pool::POOL_USAGE_FORBID && $type['export'] != ExportIntent::EXPORT_DISABLED) {
          $operations[] = [$url . '/api_unify-api_unify-connection_synchronisation-0_1', [
            'json' => [
              'id' => $local_connection_id . '--to--pool',
              'name' => 'Synchronization for ' . $entity_type_name . '/' . $bundle_name . '/' . $version . ' from ' . $site_id . ' -> Pool',
              'options' => [
                'dependency_connection_id' => self::POOL_DEPENDENCY_CONNECTION_ID,
                // As entities will only be sent to API Unify if the sync config
                // allows it, the synchronization entity doesn't need to filter
                // any further
                // 'create_entities' => TRUE,
                // 'update_entities' => TRUE,
                // 'delete_entities' => TRUE,
                // 'dependent_entities_only'  => FALSE,.
                'create_entities' => TRUE,
                'update_entities' => TRUE,
                'delete_entities' => boolval($type['export_deletion_settings']['export_deletion']),
                'force_updates' => $dcs_disable_optimization,
                'dependent_entities_only' => $export != Pool::POOL_USAGE_FORBID && $type['export'] == ExportIntent::EXPORT_AS_DEPENDENCY,
                'update_none_when_loading' => TRUE,
                'exclude_reference_properties' => [
                  'pSource',
                ],
              ],
              'status' => 'READY',
              'source_connection_id' => $local_connection_id,
              'destination_connection_id' => $pool_connection_id,
            ],
          ],
          ];
        }
      }
    }

    return $operations;
  }

  /**
   * Delete the synchronizations from this connection.
   */
  public function remove($removedOnly = TRUE) {
    return TRUE;

    // @TODO Refactor for pool changes
    $condition   = [
      'operator'  => '==',
      'values'    => [
        [
          'source'  => 'entity',
          'field'   => 'instance_id',
        ],
        [
          'source'  => 'value',
          'value'   => $this->flow->site_id,
        ],
      ],
    ];
    $url         = $this->generateUrl(
      $this->flow->url . '/api_unify-api_unify-connection-0_1',
      [
        'items_per_page'  => '99999',
        'condition' => json_encode($condition),
      ]
    );
    $response    = $this->client->{'get'}($url);
    $body        = json_decode($response->getBody(), TRUE);
    $connections = [];
    foreach ($body['items'] as $reference) {
      $connections[] = $reference['id'];
    }
    $importConnections = $connections;
    $exportConnections = $connections;

    if ($removedOnly) {
      $existingExport = [];
      $existingImport = [];
      foreach ($this->flow->getEntityTypeConfig() as $config) {
        $id = self::getExternalConnectionId(
          $this->flow->api,
          $this->flow->site_id,
          $config['entity_type_name'],
          $config['bundle_name'],
          $config['version']
        );
        if ($config['export'] != ExportIntent::EXPORT_DISABLED) {
          $existingExport[] = $id;
        }
        if ($config['import'] != ImportIntent::IMPORT_DISABLED) {
          $existingImport[] = $id;
        }
      }
      $importConnections = array_diff($importConnections, $existingImport);
      $exportConnections = array_diff($exportConnections, $existingExport);
    }
    $condition = NULL;
    if (count($exportConnections) > 0) {
      $condition = [
        'operator'    => 'in',
        'values'      => [
          [
            'source'    => 'entity',
            'field'     => 'source_connection_id',
          ],
          [
            'source'    => 'value',
            'value'     => $exportConnections,
          ],
        ],
      ];
    }
    if (count($importConnections) > 0) {
      $importCondition = [
        'operator'    => 'in',
        'values'      => [
          [
            'source'    => 'entity',
            'field'     => 'destination_connection_id',
          ],
          [
            'source'    => 'value',
            'value'     => $importConnections,
          ],
        ],
      ];
      if ($condition) {
        $condition = [
          'operator'    => 'or',
          'conditions'  => [
            $condition,
            $importCondition,
          ],
        ];
      }
      else {
        $condition = $importCondition;
      }
    }

    if (!$condition) {
      return;
    }

    $url = $this->generateUrl(
      $this->flow->url . '/api_unify-api_unify-connection_synchronisation-0_1',
      [
        'condition' => json_encode($condition),
      ]
    );
    $this->client->{'delete'}($url);
  }

}
