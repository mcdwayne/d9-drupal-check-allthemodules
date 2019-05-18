<?php

namespace Drupal\og_sm_taxonomy\Plugin\OgGroupResolver;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og_sm\SiteManagerInterface;
use Drupal\og_sm_context\Plugin\OgGroupResolver\OgSmGroupResolverBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tries to get the context based on the fact that we are on a term page.
 *
 * @OgGroupResolver(
 *   id = "og_sm_context_taxonomy",
 *   label = "Site Taxonomy",
 *   description = @Translation("Determine Site context based on the fact that we are on a Site taxonomy term.")
 * )
 */
class TaxonomyGroupResolver extends OgSmGroupResolverBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a TaxonomyGroupResolver instance.
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
    if (strpos($route_object->getPath(), '/taxonomy/term/{taxonomy_term}') !== 0) {
      return;
    }
    $term = $this->routeMatch->getParameter('taxonomy_term');
    if (is_numeric($term)) {
      $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($term);
    }

    if ($site = $this->siteManager->getSiteFromEntity($term)) {
      $collection->addGroup($site, ['url']);
      $this->stopPropagation();
    }

  }

}
