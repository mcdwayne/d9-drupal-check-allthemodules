<?php /**
 * @file
 * Contains \Drupal\tmgmt_oht\Controller\OhtController.
 */

namespace Drupal\tmgmt_oht\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\Entity\JobItem;
use Drupal\tmgmt\JobItemInterface;
use Drupal\tmgmt_oht\Plugin\tmgmt\Translator\OhtTranslator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Route controller class for the tmgmt_oht module.
 */
class OhtController extends ControllerBase {

  /**
   * Provides a callback function for OHT translator.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request to handle.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The response to return.
   */
  public function callback(Request $request) {
    $job_item_id = $request->get('custom0');
    if ($request->get('custom1') == OhtTranslator::hash($job_item_id)) {
      /** @var JobItem $job_item */
      if (!$job_item = JobItem::load($job_item_id)) {
        throw new NotFoundHttpException();
      }

      /** @var OhtTranslator $translator_plugin */
      $translator_plugin = $job_item->getTranslator()->getPlugin();
      $translator_plugin->setTranslator($job_item->getTranslator());
      $event = $request->get('event');

      // Accept new translations.
      if ($event == 'project.resources.new' && $request->get('resource_type') == 'translation') {
        $translator_plugin->retrieveTranslation([$request->get('resource_uuid')], $job_item, $request->get('project_id'));
      }
      elseif ($event == 'project.status.update' && $request->get('project_status_code') == 'cancelled') {
        // Abort the job item.
        $job_item->setState(JobItemInterface::STATE_ABORTED, 'Aborted by One Hour Translation.');
        $job_item->save();
      }

      return new Response();
    }
    else {
      \Drupal::logger('tmgmt_oht')->warning('Invalid parameters when receiving remote response for job item %id', array('%id' => $job_item_id));
      throw new NotFoundHttpException();
    }
  }

}
