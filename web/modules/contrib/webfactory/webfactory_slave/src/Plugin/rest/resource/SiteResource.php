<?php

namespace Drupal\webfactory_slave\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * SiteResource exposed a ping action.
 *
 * This call is used by master to check if the site is still live.
 *
 * @RestResource(
 *   id = "webfactory_slave:site",
 *   label = "Site",
 *   uri_paths = {
 *     "canonical" = "/webfactory_slave/site",
 *   }
 * )
 */
class SiteResource extends ResourceBase {

  /**
   * Responds to PING requests.
   *
   * @return \Drupal\rest\ResourceResponse
   *   Return HTTP 200 response.
   */
  public function get() {
    return new ResourceResponse(NULL, 200);
  }

  /**
   * Provides predefined HTTP request methods.
   *
   * Plugins can override this method to provide additional custom request
   * methods.
   *
   * @return array
   *   The list of allowed HTTP request method strings.
   */
  protected function requestMethods() {
    return [
      'GET',
    ];
  }

}
