<?php

namespace Drupal\drupal_content_sync\Plugin\rest\resource;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfo;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Url;
use Drupal\drupal_content_sync\ApiUnifyFlowExport;
use Drupal\drupal_content_sync\ApiUnifyPoolExport;
use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\Entity\MetaInformation;
use Drupal\drupal_content_sync\Entity\Pool;
use Drupal\drupal_content_sync\ImportIntent;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\Core\Render\Renderer;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides entity interfaces for Drupal Content Sync, allowing the manual
 * import dashboard to query preview entities and to import them to the site.
 *
 * @RestResource(
 *   id = "drupal_content_sync_import_entity",
 *   label = @Translation("DCS Import"),
 *   uri_paths = {
 *     "canonical" = "/rest/dcs-import/{pool}",
 *     "https://www.drupal.org/link-relations/create" = "/rest/dcs-import/{pool}/{entity_type_name}/{bundle_name}/{uuid}"
 *   }
 * )
 */
class DrupalContentSyncImportEntity extends ResourceBase {

  /**
   * @var int CODE_INVALID_DATA The provided data could not be interpreted.
   */
  const CODE_INVALID_DATA = 401;

  /**
   * @var int CODE_NOT_FOUND The entity doesn't exist or can't be accessed
   */
  const CODE_NOT_FOUND = 404;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $entityTypeBundleInfo;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderedManager;

  /**
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructs an object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfo $entity_type_bundle_info
   *   An entity type bundle info instance.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   An entity type manager instance.
   * @param \Drupal\Core\Render\Renderer $render_manager
   *   A rendered instance.
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository interface.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    EntityTypeBundleInfo $entity_type_bundle_info,
    EntityTypeManager $entity_type_manager,
    Renderer $render_manager,
    EntityRepositoryInterface $entity_repository
  ) {
    parent::__construct(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $serializer_formats,
      $logger
    );

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderedManager = $render_manager;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('renderer'),
      $container->get('entity.repository')
    );
  }

  /**
   * Responds to entity GET requests.
   *
   * @param string $pool_id
   *   The ID of the selected flow.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A list of entities of the given type and bundle.
   *
   * @throws \Exception
   */
  public function get($pool_id) {
    $pool = Pool::getAll()[$pool_id];

    $cache_build = [
      '#cache' => [
        'max-age' => 0,
      ],
    ];

    if (!$pool) {
      $resource_response = new ResourceResponse(['message' => "Unknown pool $pool_id."], self::CODE_NOT_FOUND);
      $resource_response->addCacheableDependency($cache_build);
      return $resource_response;
    }

    $entity_type_ids = [];
    $entity_type_name = isset($_GET['entity_type_name']) ? $_GET['entity_type_name'] : NULL;
    $bundle_name = isset($_GET['bundle_name']) ? $_GET['bundle_name'] : NULL;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 0;
    $url = $pool->getBackendUrl();

    foreach (Flow::getAll() as $flow) {
      foreach ($flow->getEntityTypeConfig() as $definition) {
        if (!$flow->canImportEntity($definition['entity_type_name'], $definition['bundle_name'], ImportIntent::IMPORT_MANUALLY)) {
          continue;
        }
        if ($entity_type_name && $definition['entity_type_name'] != $entity_type_name) {
          continue;
        }
        if ($bundle_name && $definition['bundle_name'] != $bundle_name) {
          continue;
        }
        if ($definition['import_pools'][$pool->id] == Pool::POOL_USAGE_FORBID) {
          continue;
        }
        $id = ApiUnifyFlowExport::getExternalEntityTypeId(
          $pool->id,
          $definition['entity_type_name'],
          $definition['bundle_name'],
          $definition['version']
        );
        if (in_array($id, $entity_type_ids)) {
          continue;
        }
        $entity_type_ids[] = $id;
      }
    }

    if (empty($entity_type_ids)) {
      $resource_response = new ResourceResponse(['message' => 'No previews available.'], self::CODE_NOT_FOUND);
      $resource_response->addCacheableDependency($cache_build);
      return $resource_response;
    }

    $url .= '/' . ApiUnifyPoolExport::EXTERNAL_PREVIEW_PATH;
    $arguments = [];
    if ($page) {
      $arguments['page'] = $page;
    }
    $arguments['items_per_page'] = 10;
    $arguments['order_by'] = json_encode(['last_update' => 'DESC', 'published_date' => 'DESC', 'id' => 'ASC']);
    $arguments['property_list'] = 'details';
    $arguments['condition'] = [
      'operator' => 'in',
      'values'   => [
        ['source' => 'data', 'field' => 'entity_type_id'],
        ['source' => 'value', 'value' => $entity_type_ids],
      ],
    ];

    $url = Url::fromUri($url, [
      'query' => $arguments,
    ])->toUriString();

    $client = \Drupal::httpClient();

    $response = $client->get($url);
    $data     = json_decode($response->getBody(), TRUE);
    if ($response->getStatusCode() != 200) {
      $resource_response = new ResourceResponse($data, $response->getStatusCode());
      $resource_response->addCacheableDependency($cache_build);
      return $resource_response;
    }

    foreach ($data['items'] as &$item) {
      $this->enrichPreviewItem($item);
    }

    $resource_response = new ResourceResponse($data);
    $resource_response->addCacheableDependency($cache_build);

    return $resource_response;
  }

  /**
   * Add meta information for the entity in question. Add "entity_type_name"
   * and "bundle" information.
   *
   * @param $item
   *
   * @throws \Exception
   */
  protected function enrichPreviewItem(&$item) {
    $uuid = $item['id'];
    $entity_type_id = $item['entity_type_id'];
    list(,, $entity_type_name, $bundle_name, $version) = explode('-', $entity_type_id);

    $item['entity_type_name'] = $entity_type_name;
    $item['bundle_name']      = $bundle_name;
    $item['version']          = $version;

    /**
     * @var \Drupal\Core\Entity\FieldableEntityInterface $entity
     */
    $entity = \Drupal::service('entity.repository')
      ->loadEntityByUuid($entity_type_name, $uuid);
    if ($entity) {
      if ($entity->hasLinkTemplate('canonical')) {
        try {
          $url = $entity->toUrl('canonical', ['absolute' => TRUE])
            ->toString(TRUE)
            ->getGeneratedUrl();
          $item['local_url'] = $url;
        }
        catch (\Exception $e) {
        }
      }
    }

    $metas = MetaInformation::getInfosForEntity($entity_type_name, $uuid);

    $item['meta_information'] = [];
    $item['last_import'] = NULL;
    $item['last_export'] = NULL;
    $item['deleted'] = FALSE;
    $item['is_source'] = FALSE;

    foreach ($metas as $info) {
      $item['meta_information'][] = [
        'flow_id' => $info->getFlow()->id,
        'pool_id' => $info->getPool()->id,
        'last_import' => $info->getLastImport(),
        'last_export' => $info->getLastExport(),
        // 'flags' => $info->flags,.
      ];
      if (!$item['last_import'] || $item['last_import'] < $info->getLastImport()) {
        $item['last_import'] = $info->getLastImport();
      }
      if (!$item['last_export'] || $item['last_export'] < $info->getLastExport()) {
        $item['last_export'] = $info->getLastExport();
      }
      if ($info->isDeleted()) {
        $item['deleted'] = TRUE;
      }
      if ($info->isSourceEntity()) {
        $item['is_source'] = TRUE;
      }
    }
  }

  /**
   * Responds to entity POST requests.
   *
   * @param string $pool_id
   * @param string $entity_type_name
   * @param string $bundle_name
   * @param string $uuid
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   -
   *
   * @throws \Exception
   */
  public function post($pool_id, $entity_type_name, $bundle_name, $uuid) {
    $pool = Pool::getAll()[$pool_id];

    if (!$pool) {
      return new ResourceResponse(['message' => "Unknown flow ID $pool_id."], self::CODE_NOT_FOUND);
    }

    $base_url = $pool->getBackendUrl();

    $response = NULL;
    $client = \Drupal::httpClient();

    foreach (Flow::getAll() as $flow) {
      foreach ($flow->getEntityTypeConfig() as $definition) {
        if (!$flow->canImportEntity($definition['entity_type_name'], $definition['bundle_name'], ImportIntent::IMPORT_MANUALLY)) {
          continue;
        }
        if ($definition['entity_type_name'] != $entity_type_name) {
          continue;
        }
        if ($definition['bundle_name'] != $bundle_name) {
          continue;
        }
        if ($definition['import_pools'][$pool->id] == Pool::POOL_USAGE_FORBID) {
          continue;
        }

        $local_connection_id = ApiUnifyFlowExport::getExternalConnectionId(
          $pool->id,
          $pool->getSiteId(),
          $entity_type_name,
          $bundle_name,
          $definition['version']
        );
        $sync_id = $local_connection_id . '--to--drupal';

        $url = $base_url . '/api_unify-api_unify-connection_synchronisation-0_1/' . $sync_id . '/clone/' . $uuid;

        $response = $client->post($url);
        $data     = json_decode($response->getBody(), TRUE);
        if ($response->getStatusCode() != 200) {
          return new ResourceResponse($data, $response->getStatusCode());
        }
      }
    }

    if (!$response) {
      return new ResourceResponse(['message' => "Missing flow for pool $pool_id."], self::CODE_NOT_FOUND);
    }

    $url = $pool->getBackendUrl();
    $url .= '/' . ApiUnifyPoolExport::EXTERNAL_PREVIEW_PATH . '/' . $uuid;

    $response = $client->get($url);
    $data     = array_merge(json_decode($response->getBody(), TRUE), $data);
    if ($response->getStatusCode() != 200) {
      return new ResourceResponse($data, $response->getStatusCode());
    }

    $this->enrichPreviewItem($data);

    return new ResourceResponse($data);
  }

}
