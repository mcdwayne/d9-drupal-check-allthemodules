<?php

namespace Drupal\prod_check_rest\Plugin\rest\resource;

use Drupal\Core\Render\RenderContext;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;

/**
 * Provides a resource for the prod check report
 *
 * @RestResource(
 *   id = "prod_check",
 *   label = @Translation("Production check log"),
 *   uri_paths = {
 *     "canonical" = "/prod_check"
 *   }
 * )
 */
class ProdCheckResource extends ResourceBase {

  /**
   * Responds to POST requests.
   *
   * Returns a resource for the prod check report
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response containing the log entry.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   */
  public function get() {
    // Some checks might render or do things that we can not properly
    // collect cache ability metadata for. So, run it in our own render
    // context.
    $context = new RenderContext();
    $result = \Drupal::service('renderer')->executeInRenderContext($context, function() {
      /** @var Rest $rest_processor */
      $rest_processor = \Drupal::service('plugin.manager.prod_check_processor')->createInstance('rest');
      return $rest_processor->requirements();
    });
    $response = $result;

    return new ResourceResponse($response, 200);
  }

}
