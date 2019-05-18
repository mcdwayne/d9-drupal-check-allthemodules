<?php

namespace Drupal\contentajs\Plugin\jsonrpc\Method;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepository;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_extras\ResourceType\ConfigurableResourceType;
use Drupal\jsonrpc\Annotation\JsonRpcMethod;
use Drupal\jsonrpc\Object\ParameterBag;
use Drupal\jsonrpc\Object\Response;
use Drupal\jsonrpc\Plugin\JsonRpcMethodBase;
use Drupal\openapi\Plugin\openapi\OpenApiGeneratorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Get metadata about JSON API.
 *
 * @JsonRpcMethod(
 *   id = "jsonapi.metadata",
 *   usage = @Translation("Get metadata about JSON API."),
 *   access = {"access content"},
 *   params = {}
 * )
 */
class JsonApiMetadata extends JsonRpcMethodBase {

  /**
   * The resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepository
   */
  protected $resourceTypeRepository;

  /**
   * The resource type repository.
   *
   * @var \Drupal\openapi\Plugin\openapi\OpenApiGeneratorManage
   */
  protected $openApiManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The base path for the JSON API.
   *
   * @var string
   */
  protected $basePath;

  /**
   * JsonApiMetadata constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ResourceTypeRepository $resource_type_repository,
    OpenApiGeneratorManager $open_api_plugin_manager,
    ConfigFactoryInterface $config_factory,
    $base_path
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->resourceTypeRepository = $resource_type_repository;
    $this->openApiManager = $open_api_plugin_manager;
    $this->configFactory = $config_factory;
    $this->basePath = $base_path;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\jsonrpc\Exception\JsonRpcException
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $resource_type_repository = $container->get('jsonapi.resource_type.repository');
    $open_api_plugin_manager = $container->get('plugin.manager.openapi.generator');
    $config_factory = $container->get('config.factory');
    $param_name = 'jsonapi.base_path';
    $base_path = $container->hasParameter($param_name)
      ? $container->getParameter($param_name)
      : '/jsonapi';
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $resource_type_repository,
      $open_api_plugin_manager,
      $config_factory,
      $base_path
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If the generator plugin cannot be found.
   */
  public function execute(ParameterBag $params) {
    // @TODO: Generate the dowloadable URL for the schema on each resource.
    /** @var \Drupal\openapi\Plugin\openapi\OpenApiGenerator\JsonApiGenerator $generator */
    $generator = $this->openApiManager->createInstance('jsonapi');
    $disabled = static::listDisabledResources($this->resourceTypeRepository);
    $generator->setOptions(['exclude' => $disabled]);
    $output = [
      'prefix' => $this->basePath,
      'openApi' => $generator->getSpecification(),
    ];
    $response = new Response('2.0', $this->currentRequest()->id(), $output);
    // Add some cacheability metatada.
    $jsonapi_config = $this->configFactory->get('jsonapi_extras.settings');
    $response->addCacheableDependency($jsonapi_config);
    // Load all the configurable resource types and add them as a cache
    // dependency.
    $resource_types = array_filter(
      $this->resourceTypeRepository->all(),
      function (ResourceType $resource_type) {
        return $resource_type instanceof ConfigurableResourceType
          && $resource_type->getJsonapiResourceConfig()->id();
      }
    );
    array_reduce(
      $resource_types,
      function (Response $response, ConfigurableResourceType $resource_type) {
        $resource_config = $resource_type->getJsonapiResourceConfig();
        $response->addCacheableDependency($resource_config);
        return $response;
      },
      $response
    );
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public static function outputSchema() {
    return [
      'type' => 'object',
      'properties' => [
        'prefix' => ['type' => 'string'],
        // TODO: Get the Open API specification schema and add it here.
        'openApi' => ['type' => 'object'],
      ],
    ];
  }

  /**
   * Lists all the disabled resource types.
   *
   * @return array
   *   The disabled resource types.
   */
  public static function listDisabledResources(ResourceTypeRepositoryInterface $resourceTypeRepository) {
    $extract_resource_type_id = function (ResourceType $resource_type) {
      return sprintf(
        '%s:%s',
        $resource_type->getEntityTypeId(),
        $resource_type->getBundle()
      );
    };
    $filter_disabled = function (ResourceType $resourceType) {
      // If there is an isInternal method and the resource is marked as internal
      // then consider it disabled. If not, then it's enabled.
      return method_exists($resourceType, 'isInternal') && $resourceType->isInternal();
    };
    $all = $resourceTypeRepository->all();
    $disabled_resources = array_filter($all, $filter_disabled);
    $disabled = array_map($extract_resource_type_id, $disabled_resources);
    return $disabled;
  }

}
