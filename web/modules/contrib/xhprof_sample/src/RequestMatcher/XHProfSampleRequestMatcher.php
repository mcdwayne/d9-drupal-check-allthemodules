<?php
/**
 * @file
 * Contains \Drupal\xhprof_sample\RequestMatcher\XHProfSampleRequestMatcher.
 */

namespace Drupal\xhprof_sample\RequestMatcher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Class XHProfSampleRequestMatcher
 */
class XHProfSampleRequestMatcher implements RequestMatcherInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  private $pathMatcher;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration service.
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
   *   Pathmatcher service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, PathMatcherInterface $pathMatcher) {
    $this->configFactory = $configFactory;
    $this->pathMatcher = $pathMatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function matches(Request $request) {
    $path = $request->getPathInfo();
    $patterns = $this->configFactory->get('xhprof_sample.settings')->get('path_enable_paths');
    return $this->pathMatcher->matchPath($path, $patterns);
  }
}
