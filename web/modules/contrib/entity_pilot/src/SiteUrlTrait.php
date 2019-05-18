<?php

namespace Drupal\entity_pilot;

/**
 * A trait for calculating a site's URL.
 */
trait SiteUrlTrait {

  /**
   * Returns the URL of this site.
   *
   * @return string
   *   URL of the site.
   */
  protected function getSite() {
    if ($link_domain = \Drupal::configFactory()->get('hal.settings')->get('link_domain')) {
      return rtrim($link_domain, '/') . '/';
    }
    $request = \Drupal::request();
    return rtrim($request->getSchemeAndHttpHost() . $request->getBasePath(), '/') . '/';
  }

}
