<?php
/**
 * @file
 * Contains \Drupal\xhprof_sample\XHProfSample\Collector.
 */

namespace Drupal\xhprof_sample\XHProfSample;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;

class Collector implements CollectorInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Drupal\xhprof_sample\XHProfSample\RunInterface
   */
  private $run;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestMatcherInterface
   */
  private $requestMatcher;

  /**
   * @var bool
   */
  private $enabled = FALSE;

  /**
   * @var int
   */
  private $startTime;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration service.
   * @param \Drupal\xhprof_sample\XHProfSample\RunInterface $run
   *   XHProfSample Run service.
   * @param \Symfony\Component\HttpFoundation\RequestMatcherInterface $requestMatcher
   *   RequestMatcher service.
   */
  public function __construct(ConfigFactoryInterface $configFactory, RunInterface $run, RequestMatcherInterface $requestMatcher) {
    $this->configFactory = $configFactory;
    $this->run = $run;
    $this->requestMatcher = $requestMatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function enable() {
    $this->startTime = microtime(TRUE);
    xhprof_sample_enable();
    $this->enabled = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function shutdown() {
    $sample_data = serialize(xhprof_sample_disable());
    $sample_metadata = $this->collectMetadata();
    $this->run->setData($sample_data);
    $this->run->setMetadata($sample_metadata);
    $this->run->save();
    $this->enabled = FALSE;

    return $sample_data;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function canEnable(Request $request) {
    $config = $this->configFactory->get('xhprof_sample.settings');
    $account = \Drupal::currentUser();

    if ($this->isLoaded() && $config->get('enabled')) {
      // Check if this request matches configured paths, inclusively or
      // exclusively depending on configuration.
      $matches = $this->requestMatcher->matches($request);
      if ($config->get('path_enable_type') == XHPROF_SAMPLE_ENABLE_PATH_NOTLISTED) {
        if ($matches) {
          return FALSE;
        }
      }
      else {
        if (!$matches) {
          return FALSE;
        }
      }

      // Check if header-based sampling is enabled, and confirm header is
      // present if so.
      if ($config->get('header_enable') === 1) {
        // @TODO: use $request object.
        if (!isset($_SERVER['HTTP_X_XHPROF_SAMPLE_ENABLE']) || !$_SERVER['HTTP_X_XHPROF_SAMPLE_ENABLE']) {
          return FALSE;
        }
      }

      return TRUE;
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isLoaded() {
    return extension_loaded('xhprof');
  }

  /**
   * Returns a structured array of run metadata.
   *
   * @return array
   *   Metadata for the collected run.
   */
  public function collectMetadata() {
    $account = \Drupal::currentUser();
    $meta = array();
    $meta['username'] = ($account->isAnonymous() ? 'anonymous' : preg_replace('/[^A-Za-z0-9]/', '', $account->getUsername()));
    $meta['runtime'] = microtime(TRUE) - $this->startTime;
    $meta['method'] = $_SERVER['REQUEST_METHOD'];
    $meta['path'] = Url::fromRoute('<current>')->toString();
    $meta['path_id'] = implode('_', explode('/', ltrim($meta['path'], '/')));

    return $meta;
  }
}
