<?php

namespace Drupal\consent_iframe\Controller;

use Drupal\Component\Utility\Unicode;
use Drupal\consent_iframe\Response\ConsentIframeResponse;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract class ConsentIframeControllerBase.
 */
abstract class ConsentIframeControllerBase implements ContainerInjectionInterface {

  /**
   * The iFrame settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $iframeSettings;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The block storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $blockStorage;

  /**
   * The system performance configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemPerformanceConfig;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')->get('consent_iframe.settings'),
      $container->get('renderer'),
      $container->get('entity_type.manager')->getStorage('block'),
      $container->get('current_user'),
      $container->get('config.factory')->get('system.performance')
    );
  }

  /**
   * ConsentIframeControllerBase constructor.
   */
  public function __construct(ImmutableConfig $iframe_settings, RendererInterface $renderer, EntityStorageInterface $block_storage, AccountProxyInterface $current_user, ImmutableConfig $system_performance_config) {
    $this->iframeSettings = $iframe_settings;
    $this->renderer = $renderer;
    $this->blockStorage = $block_storage;
    $this->currentUser = $current_user;
    $this->systemPerformanceConfig = $system_performance_config;
  }

  /**
   * Build the page content as render array.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   The page content as render array.
   */
  abstract protected function pageContent(Request $request);

  /**
   * The iFrame page controller method.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The Http response.
   */
  public function page(Request $request) {
    $cache_max_age = (int) $this->systemPerformanceConfig->get('cache.page.max_age');
    $not_cacheable = !($cache_max_age > 0) || $this->currentUser->isAuthenticated() || !$this->currentUser->isAnonymous();
    $cache_header = $not_cacheable ? 'must-revalidate, no-cache, private' : 'public, max-age=' . $cache_max_age . ' s-maxage=' . $cache_max_age;
    $build = $this->pageContent($request) + $this->pageAttachments();
    $this->renderer->renderRoot($build);
    $response = new ConsentIframeResponse($build, 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
      'Cache-Control' => $cache_header,
    ]);
    $this->setCORSHeaders($request, $response);
    if (!$not_cacheable) {
      $response->headers->remove('Pragma');
    }
    return $response;
  }

  /**
   * Get the required attachments as render array.
   *
   * @return array
   *   The attachments as render array.
   */
  protected function pageAttachments() {
    return ['#attached' => ['library' => $this->getEnabledLibraries()]];
  }

  /**
   * Get the enabled libraries.
   *
   * @return array
   *   The enabled libraries.
   */
  protected function getEnabledLibraries() {
    $libraries = ['consent/layer'];
    $trigger_libraries = [
      'storage' => 'consent/trigger.storage',
      'parent_response' => 'consent_iframe/trigger.parent_response',
    ];
    $triggers = NULL;
    if ($block_id = $this->iframeSettings->get('block')) {
      /** @var \Drupal\block\BlockInterface $block */
      if ($block = $this->blockStorage->load($block_id)) {
        $plugin_settings = $block->getPlugin()->getConfiguration();
        $triggers = isset($plugin_settings['trigger']) ? $plugin_settings['trigger'] : [];
        // Block config does not include option to enable
        // parent response trigger. Assuming to be used.
        $triggers['parent_response'] = TRUE;
      }
    }
    else {
      $triggers = $this->iframeSettings->get('trigger');
    }
    if ($triggers && is_array($triggers)) {
      foreach ($triggers as $trigger => $enabled) {
        if ($enabled && isset($trigger_libraries[$trigger])) {
          $libraries[] = $trigger_libraries[$trigger];
        }
      }
    }
    return $libraries;
  }

  /**
   * Sets proper CORS headers based on the given request.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   * @param \Symfony\Component\HttpFoundation\Response $response
   *   The corresponding response.
   */
  protected function setCORSHeaders(Request $request, Response $response) {
    $response->setVary('Origin', FALSE);
    $host = $request->getSchemeAndHttpHost();
    $origin = $request->headers->has('Referer') && !$request->headers->has('Origin') ?
      Request::create($request->headers->get('Referer'))->getSchemeAndHttpHost() : $request->headers->get('Origin');

    // Support CORS for cached Accelerated Mobile Pages (AMP).
    if ($request->query->has('__amp_source_origin')) {
      $response->setVary('AMP-Same-Origin', FALSE);
      $source_origin = $request->query->get('__amp_source_origin');
      if (($source_origin !== $host) || !Unicode::validateUtf8($source_origin)) {
        return;
      }
      if ('true' === $request->headers->get('AMP-Same-Origin')) {
        $origin = $source_origin;
      }
    }

    if (empty($origin) || !Unicode::validateUtf8($origin)) {
      return;
    }

    $is_allowed = FALSE;
    $cors_allowed = $this->iframeSettings->get('cors_allowed');
    $cors_allowed[] = $host;
    $domain = parse_url($origin, PHP_URL_HOST);
    foreach ($cors_allowed as $whitelisted) {
      if ($origin === $whitelisted) {
        $is_allowed = TRUE;
        break;
      }
      elseif (strpos($whitelisted, '*') === 0) {
        $wildcard = str_replace('*', '', $whitelisted);
        if ('' === $wildcard) {
          $is_allowed = TRUE;
          $origin = '*';
          break;
        }
      }
      elseif (preg_match('/^(.*\.)*'.$domain.'+$/', $whitelisted)) {
        $is_allowed = TRUE;
        break;
      }
    }
    if (!$is_allowed) {
      return;
    }

    // Set headers to allow cross-origin resource sharing.
    $response->headers->set('Access-Control-Allow-Origin', $origin);
    $response->headers->set('Access-Control-Allow-Credentials', 'true');
    if (!empty($source_origin)) {
      $response->headers->set('Access-Control-Expose-Headers', 'AMP-Access-Control-Allow-Source-Origin');
      $response->headers->set('AMP-Access-Control-Allow-Source-Origin', $source_origin);
    }
  }

}
