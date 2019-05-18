<?php

namespace Drupal\og_sm_context\Plugin\OgGroupResolver;

use Drupal\node\NodeInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;

/**
 * Tries to get the context based on the fact that we are on a group path.
 *
 * Will check if:
 * - The path starts with group/node/{node}
 * - If the group is a Site node type.
 *
 * @OgGroupResolver(
 *   id = "og_sm_context_group_path",
 *   label = "Site Path",
 *   description = @Translation("Determine Site context based on the fact that we are on a group path.")
 * )
 */
class GroupPathGroupResolver extends OgSmGroupResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    $route_object = $this->routeMatch->getRouteObject();
    if (!$route_object) {
      return;
    }
    if (strpos($route_object->getPath(), '/group/node/{node}/') !== 0) {
      return;
    }
    $group = $this->routeMatch->getParameter('node');

    if (!$group instanceof NodeInterface) {
      $group = $this->siteManager->load($group);
    }

    if ($group instanceof NodeInterface && $this->siteManager->isSite($group)) {
      $collection->addGroup($group, ['url']);
      $this->stopPropagation();
    }
  }

}
