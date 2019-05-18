<?php

namespace Drupal\rest_block_layout\Plugin\rest\resource;

use Drupal\block\BlockRepositoryInterface;
use Drupal\Core\Access\AccessibleInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\RefinableCacheableDependencyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamNotConvertedException;
use Drupal\Core\Url;
use Drupal\Core\Routing\AccessAwareRouterInterface;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;

/**
 * REST endpoint for block layout of a given path.
 *
 * @RestResource(
 *   id = "block_layout",
 *   label = @Translation("Block Layout"),
 *   uri_paths = {
 *     "canonical" = "/block-layout"
 *   }
 * )
 */
class BlockLayoutResource extends ResourceBase {

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Block Repository.
   *
   * @var \\Drupal\block\BlockRepositoryInterface
   */
  protected $blockRepository;

  /**
   * Router.
   *
   * @var \Drupal\Core\Routing\AccessAwareRouterInterface
   */
  protected $router;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    RequestStack $request_stack,
    BlockRepositoryInterface $block_repository,
    AccessAwareRouterInterface $router,
    EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->requestStack = $request_stack;
    $this->blockRepository = $block_repository;
    $this->router = $router;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('logger.factory')->get('rest'),
      $container->get('request_stack'),
      $container->get('block.repository'),
      $container->get('router'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function get() {
    $status = 200;
    $master = $this->requestStack->getCurrentRequest();
    $path = $master->get('path', '/');
    $entity = NULL;
    $access = NULL;
    $url = NULL;

    $cache = new CacheableMetadata();
    // Cache a different version based on the Query Args.
    $cache->addCacheContexts(['url.query_args:path']);
    // Add the block list as a cache tag.
    $cache->addCacheTags($this->entityTypeManager->getDefinition('block')->getListCacheTags());

    // Create a Request object from the specified path.
    $request = Request::create($master->getScheme() . '://' . $master->getHost() . $path);

    // Get the matched route paramaters.
    try {
      $params = $this->router->matchRequest($request);
      $entity = $this->getEntity($params);
    }
    catch (\Exception $e) {

      if ($e instanceof ResourceNotFoundException || $e instanceof ParamNotConvertedException) {
        $status = 404;
      }
      elseif ($e instanceof HttpException) {
        $status = $e->getStatusCode();
      }
      else {
        throw $e;
      }

      $codes = [401, 403, 404];

      // If the status code does not have an html page, return a serialized
      // error.
      if (!in_array($status, $codes)) {
        if ($status >= 400 && $status < 500) {
          $response = new ResourceResponse(['message' => $e->getMessage()], $status);
          $response->addCacheableDependency($cache);
          return $response;
        }
        else {
          throw $e;
        }
      }

      // If the resource is not found, return a 404 page.
      $url = Url::fromRoute('system.' . $status)->toString(TRUE);
      $request = Request::create($url->getGeneratedUrl());
      $params = $this->router->matchRequest($request);
      $entity = $this->getEntity($params);
    }

    if ($entity && $entity instanceof AccessibleInterface) {
      $access = $entity->access('view', NULL, TRUE);
    }

    // Add the params to the request.
    $request->attributes->add($params);
    $master->attributes->set('_block_layout_route', $params['_route']);

    // Push the request onto the request stack.
    $this->requestStack->push($request);

    // Get the visable blocks.
    $regions = $this->blockRepository->getVisibleBlocksPerRegion();

    // Remove the subrequest from the request stack so response middleware
    // can property use getCurrentRequest().
    $this->requestStack->pop();

    $response = new ResourceResponse($regions, $status);

    foreach ($regions as $blocks) {
      foreach ($blocks as $block) {
        $response->addCacheableDependency($block);
      }
    }

    // Add the returned entity.
    if ($entity && $entity instanceof RefinableCacheableDependencyInterface) {
      $master->attributes->set('_block_layout_entity', $entity);
      $response->addCacheableDependency($entity);
    }

    // Add the access result as a cachable dependency.
    if ($access && $access instanceof RefinableCacheableDependencyInterface) {
      $master->attributes->set('_block_layout_access', $access);
      $response->addCacheableDependency($access);
    }

    // Add the access result as a cachable dependency.
    if ($url && $url instanceof RefinableCacheableDependencyInterface) {
      $response->addCacheableDependency($url);
    }

    // Add the cache metadata.
    $response->addCacheableDependency($cache);

    return $response;
  }

  /**
   * Gets the entity object from the params.
   */
  protected function getEntity(array $params) {
    $entity_type = NULL;
    $entity = NULL;
    if (isset($params['_route'])) {
      $route_name = $params['_route'];
      $parts = explode('.', $route_name);

      if (count($parts) == 3) {
        if ($parts[0] == 'entity' && $parts[2] == 'canonical') {
          $entity_type = $parts[1];
          $entity = $params[$entity_type];
        }
      }
    }

    return $entity;
  }

}
