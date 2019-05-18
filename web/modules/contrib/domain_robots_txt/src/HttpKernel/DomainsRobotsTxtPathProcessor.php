<?php

namespace Drupal\domain_robots_txt\HttpKernel;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Processes the outbound path for robots.txt file.
 */
class DomainsRobotsTxtPathProcessor implements OutboundPathProcessorInterface {

  /**
   * We need to do it before redirects.
   *
   * @inheritdoc
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    if ($path !== '/robots.txt' || empty($options['prefix'])) {
      return $path;
    }
    unset($options['prefix']);
    return $path;
  }

}
