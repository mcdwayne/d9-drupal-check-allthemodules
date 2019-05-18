<?php

namespace Drupal\og_sm_context\Plugin\OgGroupResolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og_sm\SiteManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tries to get the context based on the fact that we are on a node page.
 *
 * @OgGroupResolver(
 *   id = "og_sm_context_node",
 *   label = "Site Content",
 *   description = @Translation("Determine Site context based on the fact that we are on a Site page or a Site content page.")
 * )
 */
class NodeGroupResolver extends OgSmGroupResolverBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a NodeGroupResolver instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Drupal\og_sm\SiteManagerInterface $site_manager
   *   The site manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, RouteMatchInterface $route_match, SiteManagerInterface $site_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $route_match, $site_manager);
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
      $container->get('current_route_match'),
      $container->get('og_sm.site_manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    $route_object = $this->routeMatch->getRouteObject();
    if (!$route_object) {
      return;
    }
    if (strpos($route_object->getPath(), '/node/{node}') !== 0) {
      return;
    }
    $node = $this->routeMatch->getParameter('node');
    if (is_numeric($node)) {
      $node = $this->entityTypeManager->getStorage('node')->load($node);
    }

    if ($this->siteManager->isSite($node)) {
      $collection->addGroup($node, ['url']);
      $this->stopPropagation();
    }
    elseif ($site = $this->siteManager->getSiteFromEntity($node)) {
      $collection->addGroup($site, ['url']);
      $this->stopPropagation();
    }

  }

}
