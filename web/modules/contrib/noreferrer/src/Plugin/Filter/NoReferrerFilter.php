<?php

namespace Drupal\noreferrer\Plugin\Filter;

use Drupal\filter\Plugin\FilterBase;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\filter\FilterProcessResult;

/**
 * Provides a filter to apply the noreferrer attribute.
 *
 * @Filter(
 *   id = "noreferrer",
 *   title = @Translation("Add link types to links"),
 *   description = @Translation("Adds <code>rel=&quot;noopener&quot;</code> to links with a target and/or <code>rel=&quot;noreferrer&quot;</code> to non-whitelisted links."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 10
 * )
 */
class NoReferrerFilter extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $modified = FALSE;
    $result = new FilterProcessResult($text);
    $html_dom = Html::load($text);
    $links = $html_dom->getElementsByTagName('a');
    $config = \Drupal::config('noreferrer.settings');
    $noopener = $config->get('noopener');
    $noreferrer = $config->get('noreferrer');
    foreach ($links as $link) {
      $types = [];
      if ($noopener && $link->getAttribute('target') !== '') {
        $types[] = 'noopener';
      }
      if ($noreferrer && ($href = $link->getAttribute('href')) && UrlHelper::isExternal($href) && !noreferrer_is_whitelisted($href)) {
        $types[] = 'noreferrer';
      }
      if ($types) {
        $rel = $link->getAttribute('rel');
        foreach ($types as $type) {
          $rel .= $rel ? " $type" : $type;
        }
        $link->setAttribute('rel', $rel);
        $modified = TRUE;
      }
    }
    if ($modified) {
      $result->setProcessedText(Html::serialize($html_dom));
    }
    return $result;
  }

}
