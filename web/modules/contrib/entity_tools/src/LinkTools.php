<?php

namespace Drupal\entity_tools;

use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Class LinkTools.
 *
 * Utilities for links.
 */
class LinkTools {

  /**
   * Renders a link.
   *
   * @todo review https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Utility!LinkGenerator.php/function/LinkGenerator%3A%3Agenerate/8.2.x
   *
   * @param string $label
   *   The link label.
   * @param \Drupal\Core\Url $url
   *   The url.
   * @param array $attributes
   *   Optional attributes like ['class' => ['internal']];.
   *
   * @return mixed|null
   *   The rendered link.
   */
  private static function renderLink($label, Url $url, array $attributes) {
    $link = Link::fromTextAndUrl($label, $url);
    $link = $link->toRenderable();
    $link['#attributes'] = $attributes;
    $output = render($link);
    return $output;
  }

  /**
   * Returns a link from a route.
   *
   * Example: 'contact.site_page'
   *
   * @param string $label
   *   The link label.
   * @param string $routeName
   *   The route name.
   * @param array $routeParameters
   *   Optional route parameter.
   * @param array $attributes
   *   Optional attributes.
   *
   * @return mixed|null
   *   The rendered link.
   */
  public static function getLinkFromRoute($label, $routeName, array $routeParameters = [], array $attributes = []) {
    // A route provided in .routing.yml.
    $url = Url::fromRoute($routeName, $routeParameters);
    return self::renderLink($label, $url, $attributes);
  }

  /**
   * Renders a link to a node id.
   *
   * @todo alias
   *
   * Syntactic sugar for getLinkFromRoute.
   *
   * @param string $label
   *   The link label.
   * @param int $nid
   *   The node id.
   * @param array $attributes
   *   Optional attributes.
   *
   * @return mixed|null
   *   The rendered link.
   */
  public static function getLinkFromNodeId($label, $nid, array $attributes = []) {
    $url = Url::fromRoute('entity.node.canonical', ['node' => $nid]);
    return self::renderLink($label, $url, $attributes);
  }

  /**
   * Returns a link from an internal path.
   *
   * Must be prefixed by a '/'
   * Example: /user/register
   * Can also be used from a route: user.register.
   *
   * @param string $label
   *   The link label.
   * @param string $path
   *   The link path.
   * @param array $attributes
   *   Optional attributes.
   *
   * @return mixed|null
   *   The rendered link.
   */
  public static function getLinkFromInternal($label, $path, array $attributes = []) {
    $url = Url::fromUri('internal:' . $path);
    return self::renderLink($label, $url, $attributes);
  }

  /**
   * Returns a link from an external Url.
   *
   * Example: https://colorfield.be.
   *
   * @param string $label
   *   The link label.
   * @param string $url
   *   Absolute url.
   * @param array $attributes
   *   Optional attributes.
   *
   * @return mixed|null
   *   The rendered link.
   */
  public static function getLinkFromExternal($label, $url, array $attributes = []) {
    $url = Url::fromUri($url);
    return self::renderLink($label, $url, $attributes);
  }

  /**
   * Returns a link to an anchor on the current page.
   *
   * @param string $label
   *   The link label.
   * @param string $anchor_name
   *   Anchor name.
   * @param array $attributes
   *   Optional attributes.
   *
   * @return mixed|null
   *   The rendered link.
   */
  public static function getCurrentAnchor($label, $anchor_name, array $attributes = []) {
    $url = Url::fromRoute('<current>', [], ['fragment' => $anchor_name]);
    return self::renderLink($label, $url, $attributes);
  }

}
