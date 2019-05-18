<?php

namespace Drupal\micro_node\HttpKernel;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\micro_node\MicroNodeFields;
use Drupal\micro_site\Entity\SiteInterface;
use Drupal\micro_site\SiteNegotiatorInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;
use Drupal\micro_node\MicroNodeManagerInterface;

/**
 * Processes the outbound path using path alias lookups for node associated with a site.
 */
class MicroNodeOutboundPathProcessor implements OutboundPathProcessorInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Site negotiator.
   *
   * @var \Drupal\micro_site\SiteNegotiatorInterface
   */
  protected $negotiator;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The micro node manager.
   *
   * @var \Drupal\micro_node\MicroNodeManagerInterface
   */
  protected $microNodeManager;

  /**
   * Constructs a NodePathProcessor object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The domain loader.
   * @param \Drupal\micro_site\SiteNegotiatorInterface $negotiator
   *   The domain negotiator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\micro_node\MicroNodeManagerInterface $micro_node_manager
   *   The micro node manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, SiteNegotiatorInterface $negotiator, ModuleHandlerInterface $module_handler, MicroNodeManagerInterface $micro_node_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->negotiator = $negotiator;
    $this->moduleHandler = $module_handler;
    $this->microNodeManager = $micro_node_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    // Only act on valid internal paths.
    if (empty($path) || !empty($options['external'])) {
      return $path;
    }
    $active_site = $this->negotiator->getActiveSite();
    $site = NULL;
    $entity = $this->getEntity($path, $options, 'node');
    if (!empty($entity) && $entity->hasField('site_id')) {
      if ($sites = $entity->get('site_id')->referencedEntities()) {
        $site = reset($sites);
      }
    }

    // If a site is specified, and the node has the unique canonical url option,
    // rewrite the link with the site base url.
    if ($site instanceof SiteInterface
      && ($site->isPublished() || $site->isRegistered())
      && !$this->microNodeManager->hasMultipleCanonicalUrl($entity)) {
      // Note that url rewrites add a leading /, which getPath() also adds.
      $options['base_url'] = trim($site->getSitePath(), '/');
      $options['absolute'] = TRUE;
    }
    // The node is not published on a micro site but on the master host, and
    // cross published on all micro sites or on some micro sites only.
    elseif ($active_site instanceof SiteInterface
      && $entity instanceof ContentEntityInterface
      && !$this->microNodeManager->hasMultipleCanonicalUrl($entity)) {
      $options['base_url'] = trim($this->microNodeManager->getMasterHostBaseUrl(), '/');
      $options['absolute'] = TRUE;
    }
    return $path;
  }

  /**
   * Derive entity data from a given path.
   *
   * @param $path
   *   The drupal path, e.g. /node/2.
   * @param $options array
   *   The options passed to the path processor.
   * @param $type
   *   The entity type to check.
   *
   * @return $entity|NULL
   */
  public static function getEntity($path, $options, $type = 'node') {
    $entity = NULL;
    if (isset($options['entity_type']) && $options['entity_type'] == $type) {
      $entity = $options['entity'];
    }
    elseif (isset($options['route'])) {
      // Derive the route pattern and check that it maps to the expected entity
      // type.
      $route_path = $options['route']->getPath();
      $entityManager = \Drupal::entityTypeManager();
      $entityType = $entityManager->getDefinition($type);
      $links = $entityType->getLinkTemplates();

      // Check that the route pattern is an entity template.
      if (in_array($route_path, $links)) {
        $parts = explode('/', $route_path);
        $i = 0;
        foreach ($parts as $part) {
          if (!empty($part)) {
            $i++;
          }
          if ($part == '{' . $type . '}') {
            break;
          }
        }
        // Get Node path if alias.
        $node_path = \Drupal::service('path.alias_manager')->getPathByAlias($path);
        // Look! We're using arg() in Drupal 8 because we have to.
        $args = explode('/', $node_path);
        if (isset($args[$i])) {
          $entity = \Drupal::entityTypeManager()->getStorage($type)->load($args[$i]);
        }
      }
    }
    return $entity;
  }

}
