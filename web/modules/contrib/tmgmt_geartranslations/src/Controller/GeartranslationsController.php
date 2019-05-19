<?php /**
 * @file
 * Contains \Drupal\tmgmt_oht\Controller\OhtController.
 */

namespace Drupal\tmgmt_geartranslations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\Entity\Job;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Route controller class for the tmgmt_oht module.
 */
class GeartranslationsController extends ControllerBase {

  /**
   * Provides a callback function for Geartranslations translator.
   *
   * @param Request $request The request to handle.
   * @return Response The response to return.
   */
  public function callback(Request $request) {
    // If it's not a JSON request, throw an exception.
    if (strpos($request->headers->get('Content-Type'), 'application/json') === false) {
      throw new BadRequestHttpException();
    }

    // Decode data, and find the related job by its id.
    $data = json_decode($request->getContent(), TRUE);
    $job_id = $data['job_id'];
    $job = \Drupal::entityTypeManager()->getStorage('tmgmt_job')->load($job_id);

    // Job not found.
    if (is_null($job)) {
      throw new NotFoundHttpException();
    }

    // Job is not active? It should!
    if (!$job->isActive()) {
      return new Response();
    }

    // Import data for each JobItem.
    foreach ($job->getItems() as $job_item) {
      $translation = $data['texts'][$job_item->id()];

      // Add the translation only if it's available on the received texts.
      if (is_array($translation)) {
        $job_item->addTranslatedData(\Drupal::service('tmgmt.data')->unflatten($translation));
      }
    }

    $job->acceptTranslation();

    return new Response();
  }

}
