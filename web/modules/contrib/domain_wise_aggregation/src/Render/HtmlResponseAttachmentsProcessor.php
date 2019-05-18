<?php

namespace Drupal\domain_wise_aggregation\Render;

use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\Core\Render\AttachmentsResponseProcessorInterface;
use Drupal\Core\Render\HtmlResponseAttachmentsProcessor as CoreHtmlResponseAttachmentsProcessor;

/**
 * Processes attachments of HTML responses.
 *
 * This class is used by the rendering service to process the #attached part of
 * the render array, for HTML responses.
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
class HtmlResponseAttachmentsProcessor extends CoreHtmlResponseAttachmentsProcessor implements AttachmentsResponseProcessorInterface {

  /**
   * {@inheritdoc}
   */
  protected function processAssetLibraries(AttachedAssetsInterface $assets, array $placeholders) {
    $variables = [];
    // Print styles - if present.
    if (isset($placeholders['styles'])) {
      // Optimize CSS if necessary, but only during normal site operation.
      $optimize_css = !defined('MAINTENANCE_MODE') && $this->config->get('css.preprocess');
      if ($optimize_css == 0) {
        $optimize_css = domain_wise_aggregation_css_domain_setting();
      }
      $variables['styles'] = $this->cssCollectionRenderer->render($this->assetResolver->getCssAssets($assets, $optimize_css));
    }

    // Print scripts - if any are present.
    if (isset($placeholders['scripts']) || isset($placeholders['scripts_bottom'])) {
      // Optimize JS if necessary, but only during normal site operation.
      $optimize_js = !defined('MAINTENANCE_MODE') && !\Drupal::state()->get('system.maintenance_mode') && $this->config->get('js.preprocess');
      if ($optimize_js == 0) {
        $optimize_js = domain_wise_aggregation_js_domain_setting();
      }
      list($js_assets_header, $js_assets_footer) = $this->assetResolver->getJsAssets($assets, $optimize_js);
      $variables['scripts'] = $this->jsCollectionRenderer->render($js_assets_header);
      $variables['scripts_bottom'] = $this->jsCollectionRenderer->render($js_assets_footer);
    }
    return $variables;
  }

}
