<?php
/**
 * @file
 * Contains Drupal\block_render\BlockRenderOutboundPathProcessor.
 */

namespace Drupal\block_render;

use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Alter the outbound paths so they are always absolute.
 */
class BlockRenderOutboundPathProcessor implements OutboundPathProcessorInterface {

  /**
   * Current route.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $route;

  /**
   * Serialization formats.
   *
   * @var array
   */
  protected $formats;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route
   *   The current route match service.
   * @param array $formats
   *   Serialization formats.
   */
  public function __construct(RouteMatchInterface $route, $formats = array()) {
    $this->route = $route;
    $this->formats = $formats;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = array(), Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL) {
    $routes = [
      'block_render.block',
    ];

    foreach ($this->getFormats() as $format) {
      $routes[] = 'rest.block_render.GET.' . $format;
      $routes[] = 'rest.block_render_multiple.GET.' . $format;
    }

    if (in_array($this->getRoute()->getRouteName(), $routes)) {
      $options['absolute'] = TRUE;
    }

    return $path;
  }

  /**
   * Gets the Current Route.
   *
   * @return \Drupal\Core\Routing\RouteMatchInterface
   *   Current Route.
   */
  public function getRoute() {
    return $this->route;
  }

  /**
   * Gets the Searlization Formats.
   *
   * @return array
   *   Searlization formats.
   */
  public function getFormats() {
    return $this->formats;
  }

}
