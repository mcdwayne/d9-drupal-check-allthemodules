<?php

namespace Drupal\amp\Render;

use Drupal\Core\Render\HtmlResponseAttachmentsProcessor;
use Drupal\Core\Asset\AssetResolverInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Asset\AssetCollectionRendererInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\amp\Routing\AmpContext;
use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Render\HtmlResponse;

/**
 * Processes attachments of AMP HTML responses.
 *
 * This class is used by the rendering service to process the #attached part of
 * the render array, for AMP HTML responses.
 *
 * To render attachments to HTML for testing without a controller, use the
 * 'bare_html_page_renderer' service to generate a
 * Drupal\Core\Render\HtmlResponse object. Then use its getContent(),
 * getStatusCode(), and/or the headers property to access the result.
 *
 * @see template_preprocess_html()
 * @see \Drupal\Core\Render\AttachmentsResponseProcessorInterface
 * @see \Drupal\Core\Render\BareHtmlPageRenderer
 * @see \Drupal\Core\Render\HtmlResponse
 * @see \Drupal\Core\Render\MainContent\HtmlRenderer
 */
class AmpHtmlResponseAttachmentsProcessor extends HtmlResponseAttachmentsProcessor {

  /**
   * The inner service that we are decorating.
   *
   * @var \Drupal\Core\Render\HtmlResponseAttachmentsProcessor
   */
  protected $htmlResponseAttachmentsProcessor;

  /**
   * The route amp context to determine whether a route is an AMP one.
   *
   * @var \Drupal\amp\Routing\AmpContext
   */
  protected $ampContext;

  /**
   * A config object for the system performance configuration.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs a HtmlResponseAttachmentsProcessor object.
   *
   * @param \Drupal\amp\Routing\AmpContext $amp_context
   *   The route amp context to determine whether the route is an amp one.
   * @param \Drupal\Core\Asset\AssetResolverInterface $asset_resolver
   *   An asset resolver.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $css_collection_renderer
   *   The CSS asset collection renderer.
   * @param \Drupal\Core\Asset\AssetCollectionRendererInterface $js_collection_renderer
   *   The JS asset collection renderer.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   */
  public function __construct(HtmlResponseAttachmentsProcessor $htmlResponseAttachmentsProcessor, AmpContext $amp_context, AssetResolverInterface $asset_resolver, ConfigFactoryInterface $config_factory, AssetCollectionRendererInterface $css_collection_renderer, AssetCollectionRendererInterface $js_collection_renderer, RequestStack $request_stack, RendererInterface $renderer, ModuleHandlerInterface $module_handler) {
    $this->htmlResponseAttachmentsProcessor = $htmlResponseAttachmentsProcessor;
    $this->ampContext = $amp_context;
    parent::__construct($asset_resolver, $config_factory, $css_collection_renderer, $js_collection_renderer, $request_stack, $renderer, $module_handler);
  }

  /**
   * {@inheritdoc}
   */
  protected function processAssetLibraries(AttachedAssetsInterface $assets, array $placeholders) {
    $variables = [];

    if ($this->ampContext->isAmpRoute()) {

      // Print styles - if present.
      if (isset($placeholders['styles'])) {
        // Optimize CSS if necessary, but only during normal site operation.
        $optimize_css = !defined('MAINTENANCE_MODE') && $this->config->get('css.preprocess');
        $variables['styles'] = $this->cssCollectionRenderer->render($this->assetResolver->getCssAssets($assets, $optimize_css));
      }

      // After css has been rendered, strip non-AMP libraries before rendering
      // the javascript.
      // @TODO This can be optional if the theme provides granular control
      // over libraries using libraries-override, and if all javascript on the
      // site came in through the libraries system.
      foreach ($assets->libraries as $delta => $library) {
        // Rather than limit the libraries to ones provided by the AMP module,
        // limit them based on an /amp. prefix, i.e. amp/amp.image. This way
        // other modules could provide libraries that won't get stripped out.
        if (strpos($library, '/amp.') === FALSE && $library != 'amp/runtime') {
          unset($assets->libraries[$delta]);
        }
      }
      // Print amp scripts - if any are present.
      if (isset($placeholders['scripts']) || isset($placeholders['scripts_bottom'])) {
        // Do not optimize JS.
        $optimize_js = FALSE;
        list($js_assets_header, $js_assets_footer) = $this->assetResolver->getJsAssets($assets, $optimize_js);
        $variables['scripts'] = $this->jsCollectionRenderer->render($js_assets_header);
      }
    }
    else {
      $variables = parent::processAssetLibraries($assets, $placeholders);
    }

    return $variables;
  }

  /**
   * {@inheritdoc}
   */
  protected function processHtmlHeadLink(array $html_head_link) {
    $attached = parent::processHtmlHeadLink($html_head_link);
    if (array_key_exists('http_header', $attached)) {
      // Find the amphtml link and flag it to be displayed as a HTTP header.
      foreach ($attached['http_header'] as $key => $value) {
        if (strpos($value[1], 'rel="amphtml"') !== FALSE) {
          $new_value = str_replace(';', '', $value[1]);
          $attached['http_header'][$key] = ['Link', $new_value, TRUE];
        }
      }
    }
    return $attached;
  }

}
