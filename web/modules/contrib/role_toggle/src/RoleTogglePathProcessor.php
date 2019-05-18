<?php

namespace Drupal\role_toggle;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

class RoleTogglePathProcessor implements OutboundPathProcessorInterface {

  /**
   * @inheritDoc
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($bubbleable_metadata) {
      RoleToggle::addCachability($bubbleable_metadata);
    }
    if (RoleToggle::hasRequestQueryCode()) {
      $options += ['query' => []];
      $options['query'] = RoleToggle::getCachedCreatedQueryCode(\Drupal::currentUser()) + $options['query'];
      // array_replace($options['query'], RoleToggle::getCachedCreatedQueryCode(\Drupal::currentUser()));
    }
    return $path;
  }

}
