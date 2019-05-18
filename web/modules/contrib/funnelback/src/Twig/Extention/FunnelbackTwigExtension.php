<?php

namespace Drupal\funnelback\Twig\Extention;

/**
 * Class FunnelbackTwigExtension.
 */
class FunnelbackTwigExtension extends \Twig_Extension {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'funnelback_filter_query_string';
  }

  /**
   * Function declarations.
   */
  public function getFunctions() {
    return [
      new \Twig_SimpleFunction('funnelback_filter_query_string', [$this, 'funnelbackFilterQueryString']),
      new \Twig_SimpleFunction('funnelback_filter_curator_link', [$this, 'funnelbackFilterCuratorLink']),
    ];
  }

  /**
   * Filter query string.
   *
   * @param string $queryString
   *   Query string.
   */
  public function funnelbackFilterQueryString($queryString) {
    return FunnelbackQueryString::filterQueryString($queryString);
  }

  /**
   * Filter query string.
   *
   * @param string $linkUrl
   *   Link URL.
   *
   * @return string
   *   Formatted link.
   */
  public function funnelbackFilterCuratorLink($linkUrl) {
    return FunnelbackQueryString::filterCuratorLink($linkUrl);
  }

}
