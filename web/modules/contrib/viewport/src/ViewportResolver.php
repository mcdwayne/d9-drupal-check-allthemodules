<?php

namespace Drupal\viewport;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;

/**
 * Provides a ViewportResolver.
 */
class ViewportResolver implements ViewportResolverInterface {

  /**
   * The patch matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  private $pathMatcher;

  /**
   * The current path stack service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  private $currentPathStack;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $viewportSettings;

  /**
   * Creates a new ViewportResolver.
   *
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   A path matcher service.
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   The current path stack.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactoryInterface
   *   The configuration factory interface.
   */
  public function __construct(PathMatcherInterface $pathMatcher, CurrentPathStack $currentPathStack, ConfigFactoryInterface $configFactoryInterface) {
    $this->pathMatcher = $pathMatcher;
    $this->currentPathStack = $currentPathStack;
    $this->viewportSettings = $configFactoryInterface->get('viewport.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isPathSelected($path = NULL) {
    if (is_null($path)) {
      $path = $this->currentPathStack->getPath();
    }
    // Normalise the pages selected and the path looked for.
    $path = strtolower($path);
    $viewport_pages = strtolower($this->viewportSettings->get('selected_pages'));

    // Check if current path is in the pages selected to have a custom viewport.
    $page_match = $this->pathMatcher->matchPath($path, $viewport_pages);
    return $page_match;
  }

  /**
   * {@inheritdoc}
   */
  public function generateViewportTagArray() {
    $viewportSettings = $this->viewportSettings;

    $values_string = '';
    $values_string .= ($width = $viewportSettings->get('width')) ? "width=$width, " : '';
    $values_string .= ($height = $viewportSettings->get('height')) ? "height=$height, " : '';
    $values_string .= ($initial_scale = $viewportSettings->get('initial_scale')) ? "initial-scale=$initial_scale, " : '';
    $values_string .= ($minimum_scale = $viewportSettings->get('minimum_scale')) ? "minimum-scale=$minimum_scale, " : '';
    $values_string .= ($maximum_scale = $viewportSettings->get('maximum_scale')) ? "maximum-scale=$maximum_scale, " : '';
    $values_string .= ($viewportSettings->get('user_scalable') == TRUE) ? "user-scalable=yes" : 'user-scalable=no';

    $viewport_tag = array(
      '#tag' => 'meta',
      '#attributes' => array(
        'name' => 'viewport',
        'content' => $values_string,
      ),
    );
    return $viewport_tag;
  }

}
