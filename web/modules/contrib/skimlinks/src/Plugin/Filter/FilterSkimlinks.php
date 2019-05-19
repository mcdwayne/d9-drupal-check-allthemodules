<?php

namespace Drupal\skimlinks\Plugin\Filter;

use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;

/**
 * @Filter(
 *   id = "filter_skimlinks",
 *   title = @Translation("Skimlinks link filter"),
 *   description = @Translation("Transforms links to Skimlinks affiliate marketing links if the 'server-side' settings are enabled in the Skimlinks configuration. Only functions on nodes."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   weight = 1000,
 *   status = TRUE,
 * )
 *
 * @todo enable for non-node entities.
 */
class FilterSkimlinks extends FilterBase {

  private $config;
  private $node;

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $this->skimlinksConfig = \Drupal::config('skimlinks.settings');
    $this->node = \Drupal::routeMatch()->getParameter('node');

    if (
      /**
       * @todo We can't straightforwardly get the view mode here. How vital is
       * it that we retain this functionality? Why rewrite only on certain
       * modes?
       */
      // Check if this view mode is configured to be processed.
      // in_array($view_mode, $this->skimlinksConfig->get('displays') ?: ['full'])
      // Check that Server side implementation is enabled.
      ($this->skimlinksConfig->get('environment') ?: 0) == 1
    ) {
      $text = $this->updateUrls($text);
    }

    return new FilterProcessResult($text);
  }

  /**
   * Traverses html looking for links and updates them if appropriate.
   */
  private function updateUrls($text) {
    $doc = Html::load($text);

    foreach (_skimlinks_get_external_links($doc) as $domElement) {
      $href = $domElement->getAttribute('href');

      if (
        UrlHelper::isExternal($href)
        && skimlinks_domain_allowed($href)
      ) {
        // Change the link.
        $redirect = _skimlinks_get_redirect_url($href, $this->node);
        $domElement->setAttribute('href', $redirect);

        if ($this->skimlinksConfig->get('link_new_window') !== NULL ? $this->skimlinksConfig->get('link_new_window') : 1) {
          $domElement->setAttribute('target', '_blank');
        }

        if ($this->skimlinksConfig->get('link_nofollow') !== NULL ? $this->skimlinksConfig->get('link_nofollow') : 1) {
          $domElement->setAttribute('rel', 'nofollow');
        }

        $changed = TRUE;
      }

      // Create a stub domain entry if we don't know about the domain yet.
      // It will be checked and updated on cron.
      if ($this->skimlinksConfig->get('api_cron_enabled')) {
        if (\Drupal\Component\Utility\UrlHelper::isExternal($href)) {
          $domain = _skimlinks_get_host($href);
          if (!empty($domain) && !_skimlinks_domain_exists($domain)) {
            skimlinks_known_domains_update($domain);
          }
        }
      }
    }

    return HTML::serialize($doc);
  }

}
