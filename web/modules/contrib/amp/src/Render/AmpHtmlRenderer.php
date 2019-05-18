<?php

namespace Drupal\amp\Render;

use Drupal\Core\Render\MainContent\HtmlRenderer;
use Drupal\Core\Controller\TitleResolverInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Render\RenderCacheInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\HtmlResponse;
use Drupal\amp\Service\AMPService;
use Lullabot\AMP\Validate\Scope;
use Drupal\Component\Utility\Xss;

/**
 * Default main content renderer for AMPHTML requests.
 *
 * @see template_preprocess_html()
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 */
class AmpHtmlRenderer extends HtmlRenderer {

  /**
   * @var \Drupal\amp\Service\AMPService
   */
  protected $ampService;

  /**
   * Constructs a new HtmlRenderer.
   *
   * @param \Drupal\Core\Controller\TitleResolverInterface $title_resolver
   *   The title resolver.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $display_variant_manager
   *   The display variant manager.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Render\RenderCacheInterface $render_cache
   *   The render cache service.
   * @param array $renderer_config
   *   The renderer configuration array.
   * @param \Drupal\amp\Service\AMPService $amp_service
   *   The AMP service.
   */
  public function __construct(TitleResolverInterface $title_resolver, PluginManagerInterface $display_variant_manager, EventDispatcherInterface $event_dispatcher, ModuleHandlerInterface $module_handler, RendererInterface $renderer, RenderCacheInterface $render_cache, array $renderer_config, AMPService $amp_service) {
    $this->titleResolver = $title_resolver;
    $this->displayVariantManager = $display_variant_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
    $this->renderCache = $render_cache;
    $this->rendererConfig = $renderer_config;
    $this->ampService = $amp_service;
  }

  /**
   * {@inheritdoc}
   *
   * Copy of Drupal\Core\Render\MainContent\HtmlRenderer:renderResponse()
   * with two important differences:
   *
   * - the page is run through renderRoot() instead of render() to force
   *   placeholders to be replaced on the server, because Big Pipe and other
   *   placeholder replacement javascript won't be available on the client.
   *
   * - the final page markup may be also be run through the AMP converter,
   *   depending on configuration in the AMP module.
   *
   * @TODO Need to watch for changes to parent method and mirror them here.
   */
  public function renderResponse(array $main_content, Request $request, RouteMatchInterface $route_match) {
    list($page, $title) = $this->prepare($main_content, $request, $route_match);

    if (!isset($page['#type']) || $page['#type'] !== 'page') {
      throw new \LogicException('Must be #type page');
    }

    $page['#title'] = $title;

    // Now render the rendered page.html.twig template inside the html.html.twig
    // template, and use the bubbled #attached metadata from $page to ensure we
    // load all attached assets.
    $html = [
      '#type' => 'html',
      'page' => $page,
    ];

    $html['page']['#cache']['contexts'] += ['url.query_args:amp'];
    if ($this->ampService->isDevPage()) {
      $html['page']['#cache']['contexts'] += ['url.query_args:development'];
    }

    // The special page regions will appear directly in html.html.twig, not in
    // page.html.twig, hence add them here, just before rendering html.html.twig.
    $this->buildPageTopAndBottom($html);

    // Render and replace placeholders using RendererInterface::renderRoot()
    // instead of RendererInterface::render().
    // @see \Drupal\Core\Render\HtmlResponseAttachmentsProcessor.
    $render_context = new RenderContext();
    $this->renderer->executeInRenderContext($render_context, function () use (&$html) {
      // @todo Simplify this when https://www.drupal.org/node/2495001 lands.
      $this->renderer->renderRoot($html);
    });
    $content = $this->renderCache->getCacheableRenderArray($html);

    // See if the page markup should be run through the AMP converter.
    if (!empty($this->ampService->ampConfig('process_full_html'))) {

      // Replacing the entire page won't work because the page head still
      // contains placeholders for libraries and css. So replace only the
      // contents of <body>.
      $amp = $this->ampService->createAMPConverter();
      $markup = $content['#markup']->__toString();

      // Retrieve the internal contents of <body></body> and run it through AMP.
      $pattern = "/<body[^>]*>(.*?)<\/body>/is";
      preg_match($pattern, $markup, $matches);
      $body = $matches[1];
      $amp->loadHtml($body);
      $replaced_body = $amp->convertToAmpHtml();

      // Find the replaced body tag attributes.
      $attr_pattern = "/<body[^>](.*?)>/is";
      preg_match($attr_pattern, $markup, $matches);
      $attributes = $matches[1];

      // Reconstruct the page with the updated body.
      $replaced_body = '<body ' . $attributes . '>' . $replaced_body . '<body>';
      $content['#markup'] = preg_replace($pattern, $replaced_body, $markup);
      $content['#allowed_tags'] = array_merge(Xss::getAdminTagList(), ['amp-img']);

      // Add additional required javascript libraries, if found. No worry about
      // duplication of previously-added libraries since Drupal's libraries
      // system will properly handle that.
      if (!empty($amp->getComponentJs())) {
        $libraries = $this->ampService->addComponentLibraries($amp->getComponentJs());
        $content['#allowed_tags'] = array_merge($this->ampService->getComponentTags($amp->getComponentJs()), $content['#allowed_tags']);
      }

      // If development messages are displayed, display the changes made to the
      // markup as a diff.
      if (!empty($amp->getInputOutputHtmlDiff())) {
        $title = '<h2>' . t('AMP converter changes') . '</h2>';
        $pre = '<div>' . t('The AMP converter made the following changes to ' .
          'this page. If you do not want this behavior, turn off the option ' .
          'to <strong>Run the page body through the AMP converter</strong> ' .
          'in the AMP settings.') .
          '</div>';
        $this->ampService->devMessage($title . $pre . '<pre>' . $amp->getInputOutputHtmlDiff() . '</pre>');
      }
      $content['#attached']['library'] = array_merge($content['#attached']['library'], $libraries);

    }

    // Also associate the "rendered" cache tag. This allows us to invalidate the
    // entire render cache, regardless of the cache bin.
    $content['#cache']['tags'][] = 'rendered';

    $response = new HtmlResponse($content, 200, [
      'Content-Type' => 'text/html; charset=UTF-8',
    ]);

    return $response;
  }

}
