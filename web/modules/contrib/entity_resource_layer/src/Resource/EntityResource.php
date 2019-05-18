<?php

namespace Drupal\entity_resource_layer\Resource;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\entity_resource_layer\EntityResourceLayerManager;
use Drupal\entity_resource_layer\Exception\EntityResourceException;
use Drupal\entity_resource_layer\Exception\EntityResourceFieldException;
use Drupal\entity_resource_layer\Exception\EntityResourceMultipleException;
use Drupal\rest\Plugin\rest\resource\EntityResource as OriginalResource;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\RouteCollection;

/**
 * Replacement of entity resource plugin class.
 *
 * @package Drupal\entity_resource_layer\Resource
 */
class EntityResource extends OriginalResource {

  /**
   * The entity resource layers plugin layer manager.
   *
   * @var \Drupal\entity_resource_layer\EntityResourceLayerManager
   */
  protected $resourceLayerManager;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, array $serializer_formats, LoggerInterface $logger, ConfigFactoryInterface $config_factory, PluginManagerInterface $link_relation_type_manager, EntityResourceLayerManager $resourceLayerManager, CurrentRouteMatch $routeMatch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $serializer_formats, $logger, $config_factory, $link_relation_type_manager);
    $this->resourceLayerManager = $resourceLayerManager;
    $this->routeMatch = $routeMatch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('rest'),
      $container->get('config.factory'),
      $container->get('plugin.manager.link_relation_type'),
      $container->get('plugin.manager.entity_resource_layer'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $collection = new RouteCollection();

    $definition = $this->getPluginDefinition();
    $canonPath = isset($definition['uri_paths']['canonical']) ? $definition['uri_paths']['canonical'] : '/' . strtr($this->pluginId, ':', '/') . '/{id}';
    $createPath = isset($definition['uri_paths']['https://www.drupal.org/link-relations/create']) ? $definition['uri_paths']['https://www.drupal.org/link-relations/create'] : '/' . strtr($this->pluginId, ':', '/');

    $routeName = strtr($this->pluginId, ':', '.');
    $collection->addCollection($this->getRoutesWithPath($canonPath, $createPath, $routeName));

    // Gather additional defined paths from resource plugins for the endpoints.
    $entityType = $this->entityType->id();
    foreach ($this->resourceLayerManager->getDefinitions() as $id => $layer) {
      if (!isset($layer['additionalPath']) || !$layer['additionalPath'] || $layer['entityType'] != $entityType) {
        continue;
      }

      if (strpos($layer['additionalPath'], "{$entityType}") === FALSE) {
        throw new \LogicException(sprintf('The additional path must contain the route parameter {%s}. Defined as "%s" in the "%s" resource plugin.', $entityType, $layer['additionalPath'], $id));
      }

      $bundle = $this->resourceLayerManager->getBundleMapFromDefinition($definition);
      if (is_array($bundle)) {
        $bundle = implode(',', $bundle);
      }

      // The post paths don't have entity.
      list($createPath) = explode('{', $layer['additionalPath']);
      $createPath = rtrim($createPath, '/');
      $routes = $this->getRoutesWithPath($layer['additionalPath'], $createPath, "$routeName.$id");

      // Require routes to be with specified bundle.
      foreach ($routes as $route) {
        $route->addOptions([
          'parameters' => [
            $entityType => ['type' => "entity_bundle:$entityType:$bundle"]
          ],
        ]);
      }

      $collection->addCollection($routes);
    }

    return $collection;
  }

  /**
   * Generates base routes for give path.
   *
   * @param string $path
   *   The main path.
   * @param string $createPath
   *   The create path.
   * @param string $routeName
   *   The base route name.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The generated routes.
   */
  protected function getRoutesWithPath($path, $createPath, $routeName) {
    $collection = new RouteCollection();
    $entityTypeId = $this->entityType->id();
    $implementsContentInterface = $this->entityType->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface');

    foreach ($this->availableMethods() as $method) {
      $route = $this->getBaseRoute('/api' . $path, $method);

      $versionedRoute = $this->getBaseRoute('/api/{_version}' . $path, $method);
      $versionedRoute->addRequirements(['_version' => 'v\d+']);

      // For content entities we know for sure that all IDs are numeric.
      // Because of this we can set the requirement for the parameter to be only
      // numeric and by this we allow for other routes with different params
      // for the same path.
      if ($method != 'POST' && $implementsContentInterface) {
        $route->setRequirement($entityTypeId, '\d+');
        $versionedRoute->setRequirement($entityTypeId, '\d+');
      }

      switch ($method) {
        case 'POST':
          $route->setPath('/api' . $createPath);
          $collection->add("$routeName.$method", $route);

          $versionedRoute->setPath('/api/{_version}' . $createPath);
          $collection->add("$routeName.versioned.$method", $versionedRoute);
          break;

        case 'GET':
        case 'HEAD':
          // Restrict GET and HEAD requests to the media type specified in the
          // HTTP Accept headers.
          foreach ($this->serializerFormats as $format_name) {
            // Expose one route per available format.
            $formatRoute = clone $route;
            $formatRoute->addRequirements(['_format' => $format_name]);
            $collection->add("$routeName.$method.$format_name", $formatRoute);

            $formatRoute = clone $versionedRoute;
            $formatRoute->addRequirements(['_format' => $format_name]);
            $collection->add("$routeName.versioned.$method.$format_name", $formatRoute);
          }
          break;

        default:
          $collection->add("$routeName.$method", $route);
          $collection->add("$routeName.versioned.$method", $versionedRoute);
          break;
      }
    }

    return $collection;
  }

  /**
   * Get the resource layers for entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\entity_resource_layer\EntityResourceLayerPluginInterface[]
   *   Layer plugins.
   */
  protected function getResourceLayers(EntityInterface $entity) {
    if ($version = $this->routeMatch->getParameter('_version')) {
      $version = substr($version, 1);
    }

    $layers = $this->resourceLayerManager->getAdaptors(
      $entity->getEntityTypeId(),
      $entity->bundle(),
      $version
    );

    if (!is_null($version) && !count($layers)) {
      throw new NotFoundHttpException(sprintf('API version %s does not exist.', $version));
    }

    return $layers;
  }

  /**
   * {@inheritdoc}
   */
  public function get(EntityInterface $entity) {
    return $this->delegateMethod('get', [$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function post(EntityInterface $entity = NULL) {
    return $this->delegateMethod('post', [$entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function patch(EntityInterface $original_entity, EntityInterface $entity = NULL) {
    return $this->delegateMethod('patch', [$original_entity, $entity]);
  }

  /**
   * {@inheritdoc}
   */
  public function delete(EntityInterface $entity) {
    return $this->delegateMethod('delete', [$entity]);
  }

  /**
   * Delegates the REST method to the layers.
   *
   * @param string $method
   *   The method name.
   * @param array $args
   *   The arguments to send.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response.
   */
  protected function delegateMethod($method, array $args) {
    /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
    $entity = $args[0];
    $layers = $this->getResourceLayers($entity);

    $ucMethod = ucfirst($method);
    $upMethod = strtoupper($method);

    // Ensure we have access for the operation.
    foreach ($layers as $layer) {
      $access = $layer->checkAccess($entity, $upMethod);

      if ($access->isAllowed()) {
        // If a plugin explicitly allows access with higher priority then we
        // don't check the lower priority plugins access.
        break;
      }
      elseif ($access->isForbidden()) {
        $message = $access->getReason() ?? sprintf('Access denied on %s for entity %s:%d', $method, $entity->getEntityType()->getLabel(), $entity->id());
        throw new AccessDeniedHttpException($message);
      }
    }

    // Call all before handlers. These can break the operation by returning a
    // response.
    foreach ($layers as $layer) {
      if ($response = call_user_func_array([$layer, 'before' . $ucMethod], $args)) {
        return $response;
      }
    }

    // Execute original parent CRUD.
    $response = call_user_func_array("parent::$method", $args);
    // Call all post execution handlers. These can alter the generated response.
    foreach ($layers as $layer) {
      $response = call_user_func_array([$layer, 'react' . $ucMethod], array_merge([$response], $args));

      if (!$response) {
        throw new \LogicException(sprintf('Method "%s" of %s has to return a response.', [
          get_class($layer), 'react' . $ucMethod,
        ]));
      }
    }

    return $response;
  }

  /**
   * Validates the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   */
  public function validate(EntityInterface $entity) {
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $fieldManager */
    $fieldManager = \Drupal::service('entity_field.manager');
    $fieldDefinitions = $fieldManager->getFieldDefinitions(
      $entity->getEntityTypeId(),
      $entity->bundle() ?: $entity->getEntityTypeId()
    );

    $layers = $this->getResourceLayers($entity);;

    // Get the highest priority layer. We will use the field mapping from
    // this, as combining does not make much sense.
    $firstLayer = reset($layers);
    $fieldMap = $firstLayer->getFieldsMapping(array_keys($fieldDefinitions));

    // We will be combining multiple exceptions and sending all of them at once.
    $exceptionCollection = new EntityResourceMultipleException(
      $this->t('Invalid @label fields.', ['@label' => $entity->getEntityType()->getLabel()])
    );

    // Add custom violations from layer plugins. We do this first so we can
    // allow for plugins to add additional validation constraints.
    foreach ($layers as $layer) {
      try {
        $layer->validate($entity);
      }
      catch (EntityResourceMultipleException $layerException) {
        // Allow for multiple parallel exceptions.
        $exceptionCollection->addFrom($layerException);
      }
      catch (\Exception $layerException) {
        $exceptionCollection->addException($layerException);
      }
    }

    // Some violations can fail and throw exceptions. We still want to
    // provide as good as possible feedback to the consumer.
    try {
      $violations = $entity->validate();
    }
    catch (\Exception $e) {
      throw new EntityResourceException($e->getMessage());
    }

    $violations->filterByFieldAccess();

    foreach ($violations as $violation) {
      $exceptionCollection->addException((new EntityResourceFieldException(
        PlainTextOutput::renderFromHtml($violation->getMessage()),
        $violation->getPropertyPath()
      ))->addConstraintInformation($violation->getConstraint()));
    }

    // Throw only if we have exceptions.
    if (!$exceptionCollection->hasException()) {
      return;
    }

    // Map the field names in the exception data so that the consumer knows
    // which fields are erroneous.
    foreach ($exceptionCollection->getExceptions() as $exception) {
      if (!$exception instanceof EntityResourceFieldException) {
        continue;
      }

      $fieldPath = explode('.', $exception->getFieldName());
      $fieldPath[0] = array_key_exists($fieldPath[0], $fieldMap) ? $fieldMap[$fieldPath[0]] : $fieldPath[0];

      $exception->setFieldName(implode('.', $fieldPath));
    }

    throw $exceptionCollection;
  }

}
