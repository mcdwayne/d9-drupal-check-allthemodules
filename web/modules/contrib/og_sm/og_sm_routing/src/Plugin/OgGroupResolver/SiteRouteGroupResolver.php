<?php

namespace Drupal\og_sm_routing\Plugin\OgGroupResolver;

use Drupal\node\NodeInterface;
use Drupal\og\OgResolvedGroupCollectionInterface;
use Drupal\og_sm_context\Plugin\OgGroupResolver\OgSmGroupResolverBase;

/**
 * Tries to get the context for site routes
 *
 * Will check if:
 * - The site routes has a "og_sm_routing:site" parameter.
 *
 * @OgGroupResolver(
 *   id = "og_sm_context_site_route",
 *   label = "Site Route",
 *   description = @Translation("Determine Site context based on a site route.")
 * )
 */
class SiteRouteGroupResolver extends OgSmGroupResolverBase {

  /**
   * {@inheritdoc}
   */
  public function resolve(OgResolvedGroupCollectionInterface $collection) {
    $group = $this->routeMatch->getParameter('og_sm_routing:site');
    if ($group instanceof NodeInterface && $this->siteManager->isSite($group)) {
      $collection->addGroup($group, ['url']);
      $this->stopPropagation();
    }
  }

}
