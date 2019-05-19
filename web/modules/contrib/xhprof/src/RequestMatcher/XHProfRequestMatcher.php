<?php

namespace Drupal\xhprof\RequestMatcher;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\PathMatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

/**
 * Class WebprofilerRequestMatcher
 */
class XHProfRequestMatcher implements RequestMatcherInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  private $pathMatcher;

  /**
   * @param ConfigFactoryInterface $configFactory
   * @param \Drupal\Core\Path\PathMatcherInterface $pathMatcher
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

    $patterns = $this->configFactory->get('xhprof.config')->get('exclude');

    // never collect phpinfo page.
    $patterns .= "\r\n/admin/reports/status/php";

    return !$this->pathMatcher->matchPath($path, $patterns);
  }
}
