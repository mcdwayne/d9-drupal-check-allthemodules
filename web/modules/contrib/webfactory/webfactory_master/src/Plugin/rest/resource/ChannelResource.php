<?php

namespace Drupal\webfactory_master\Plugin\rest\resource;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Drupal\webfactory\EntityExportEvent;
use Drupal\webfactory\WebfactoryException;
use Drupal\webfactory_master\Entity\ChannelEntity;
use Drupal\webfactory_master\Entity\SatelliteEntity;
use Drupal\webfactory_master\Plugin\ChannelSourcePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Route;
use \Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Channel ressource.
 *
 * @RestResource(
 *   id = "webfactory_master:channel",
 *   label = "Channel",
 *   uri_paths = {
 *     "canonical" = "/webfactory_master/channel",
 *     "list_channels" = "/webfactory_master/channels/{satellite_id}",
 *   }
 * )
 */
class ChannelResource extends ResourceBase {

  /**
   * The logger factory service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Channel source manager.
   *
   * @var ChannelSourcePluginManager
   */
  protected $channelSourceManager;

  /**
   * Current request.
   *
   * @var Request
   */
  protected $request;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The entity repository service.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * Constructor.
   *
   * @param array $configuration
   *   Configuration.
   * @param string $plugin_id
   *   Plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param array $serializer_formats
   *   Serializer format.
   * @param LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   * @param ChannelSourcePluginManager $channel_source_manager
   *   Channel source manager.
   * @param Request $request
   *   Current request.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   Event dispatcher.
   * @param EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      array $serializer_formats,
      LoggerChannelFactoryInterface $logger_factory,
      ChannelSourcePluginManager $channel_source_manager,
      Request $request,
      EventDispatcherInterface $event_dispatcher,
      EntityRepositoryInterface $entity_repository
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger_factory->get('rest'));
    $this->loggerFactory = $logger_factory;
    $this->channelSourceManager = $channel_source_manager;
    $this->request = $request;
    $this->eventDispatcher = $event_dispatcher;
    $this->entityRepository = $entity_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory'),
      $container->get('plugin.manager.webfactory_master.channel'),
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('event_dispatcher'),
      $container->get('entity.repository')
    );
  }

  /**
   * Responds to GET requests.
   *
   * Returns a watchdog log entry for the specified ID.
   *
   * @return ResourceResponse
   *   The response containing the log entry.
   */
  public function get() {
    $method = strtoupper(__FUNCTION__);
    $args = func_get_args();

    $route_name = $this->request->get('_route');
    $format = $this->request->getRequestFormat();

    switch ($route_name) {
      case 'rest.' . $this->getRouteName($method, $format, 'list_channels'):
        // List all channels available.
        $satellite_id = $args[0];

        $channel_entities = $this->getChannels($satellite_id);
        $datas = $channel_entities;
        break;

      case 'rest.' . $this->getRouteName($method, $format):
        // List one/all entity(ies) of a specific channel.
        // See in the route for parameters position taking default values
        // into account.
        $uuid = $args[0];
        $channel_id = $args[1];

        $entities = $this->getEntities($uuid, $channel_id);
        if (!empty($entities['entities'])) {
          $datas = $entities;
        }
        break;
    }

    $this->loggerFactory->get('webfactory')->debug($this->request->getRequestUri());

    if (isset($datas)) {
      return new ResourceResponse($datas);
    }
    else {
      throw new HttpException(t('No data found or no valid parameters was provided'));
    }
  }

  /**
   * Get channels per satellite.
   *
   * @param int $satellite_id
   *   Satellite entity id.
   *
   * @return static[]
   *   An array of satellite entity objects indexed by their IDs.
   */
  protected function getChannels($satellite_id) {
    $satellite_entity = SatelliteEntity::load($satellite_id);
    $channels = $satellite_entity->get('channels');

    return ChannelEntity::loadMultiple($channels);
  }

  /**
   * Get entities.
   *
   * @param string $uuid
   *   The ID of the watchdog log entry.
   * @param string $channel_id
   *   The channel id.
   *
   * @return array|void
   *   Response.
   *
   * @throws WebfactoryException
   */
  protected function getEntities($uuid = NULL, $channel_id = NULL) {
    $limit = $this->request->get('limit');
    $offset = $this->request->get('offset');

    $channel_entity = ChannelEntity::load($channel_id);

    if (empty($channel_entity)) {
      throw new WebfactoryException("Invalid channel");
    }

    $entity_type = $channel_entity->get('entity_type');
    $channel_source = $channel_entity->get('source');
    $settings = $channel_entity->get('settings');

    $entities = [];

    if ($uuid !== NULL) {
      $entities[] = $this->entityRepository->loadEntityByUuid($entity_type, $uuid);
      $nb_total = 1;
    }
    else {
      /** @var \Drupal\webfactory_master\Plugin\ChannelSourceInterface $channel */
      $channel = $this->channelSourceManager->createInstance($channel_source);

      $channel->setConfiguration($channel_entity, $settings);
      $entities = $channel->entities($limit, $offset);
      $nb_total = $channel->getNbTotalEntities();
    }

    foreach ($entities as $entity) {
      // Dispatch event for altering entity export with new field management.
      $this->eventDispatcher->dispatch(EntityExportEvent::EVENT_NAME, new EntityExportEvent($entity));
    }

    if (!empty($entities)) {
      return $this->prepareEntitiesResponse(
        $entity_type,
        $entities,
        $nb_total
      );
    }
  }

  /**
   * Prepare entities response.
   *
   * @param string $entity_type
   *   The entity type.
   * @param array $entities
   *   List of entities.
   * @param int $nb_total_entities
   *   Total number of entities matching the query.
   *
   * @return array
   *   Response.
   */
  protected function prepareEntitiesResponse($entity_type, array $entities, $nb_total_entities) {
    $response = [
      'entity_type' => $entity_type,
      'entities' => [],
      'nb_total_entities' => $nb_total_entities,
    ];

    foreach ($entities as $entity) {
      $bundle = $entity->bundle();
      if (!isset($response['entities'][$bundle])) {
        $response['entities'][$bundle] = [$entity];
      }
      else {
        $response['entities'][$bundle][] = $entity;
      }
    }
    return $response;
  }

  /**
   * Provides predefined HTTP request methods.
   *
   * Plugins can override this method to provide additional custom request
   * methods.
   *
   * @return array
   *   The list of allowed HTTP request method strings.
   */
  protected function requestMethods() {
    return array(
      'GET',
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBaseRoute($canonical_path, $method) {
    $lower_method = strtolower($method);

    $default = [
      '_controller' => 'Drupal\rest\RequestHandler::handle',
      // Pass the resource plugin ID along as default property.
      '_plugin' => $this->pluginId,
    ];

    $parameters = [];
    switch ($method) {
      case 'GET':
        $canonical_path .= '/{channel_id}/{uuid}';
        $default['uuid'] = NULL;
        break;
    }

    $route = new Route($canonical_path, $default,
      array(
        '_permission' => "restful $lower_method $this->pluginId",
      ),
      $parameters,
      '',
      array(),
      // The HTTP method is a requirement for this route.
      array($method)
    );

    return $route;
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $collection = parent::routes();
    $this->alterRouteCollection($collection);
    return $collection;
  }

  /**
   * Alter the route collection.
   *
   * Add dynamically the list_channels route.
   *
   * @param RouteCollection $collection
   *   Collection of routes.
   */
  protected function alterRouteCollection(RouteCollection $collection) {
    $route_name = strtr($this->pluginId, ':', '.');
    $method = 'GET';
    $lower_method = strtolower($method);
    $definition = $this->getPluginDefinition();

    $channels_path = $definition['uri_paths']['list_channels'];
    $defaults = [
      '_controller' => 'Drupal\rest\RequestHandler::handle',
      // Pass the resource plugin ID along as default property.
      '_plugin' => $this->pluginId,
    ];
    $requirements = array(
      '_permission' => "restful $lower_method $this->pluginId",
    );
    $options = [];

    $route = new Route(
      $channels_path,
      $defaults,
      $requirements,
      $options,
      '',
      array(),
      // The HTTP method is a requirement for this route.
      array($method)
    );

    // Generate a route per format.
    foreach ($this->serializerFormats as $format_name) {
      // Expose one route per available format.
      $format_route = clone $route;
      $format_route->addRequirements(array('_format' => $format_name));
      $collection->add("$route_name.$method.$format_name.list_channels", $format_route);
    }
  }

  /**
   * Get the route name.
   *
   * @param string $method
   *   HTTP Method.
   * @param string $format
   *   Format asked.
   * @param null|string $sub_action
   *   Action of the route.
   *
   * @return string
   *   The route name.
   */
  protected function getRouteName($method, $format, $sub_action = NULL) {
    $method = strtoupper($method);

    $route_name = strtr($this->pluginId, ':', '.');
    $route_name = "$route_name.$method.$format";

    if (!is_null($sub_action)) {
      $route_name .= ".$sub_action";
    }

    return $route_name;
  }

}
