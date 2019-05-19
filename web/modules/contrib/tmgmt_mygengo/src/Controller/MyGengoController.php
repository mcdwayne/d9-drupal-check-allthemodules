<?php

/**
 * @file
 * Contains \Drupal\tmgmt_mygengo\Controller\MyGengoController.
 */

namespace Drupal\tmgmt_mygengo\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\Entity\Job;
use Drupal\tmgmt\Entity\JobItem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\tmgmt\Entity\RemoteMapping;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Route controller class for the tmgmt translator entity.
 */
class MyGengoController extends ControllerBase {

  /**
   * Process response from mygengo.
   */
  public function callback(Request $request) {
    // First, check if this is a comment notification.
    if ($request->request->get('comment')) {
      $comment = Json::decode($request->request->get('comment'));
      \Drupal::cache('data')->delete('tmgmt_mygengo_comments_' . $comment['job_id']);
      return new Response();
    }

    // Check if we have a job.
    if (!($request->request->get('job'))) {
      throw new NotFoundHttpException;
    }

    $data = Json::decode($request->request->get('job'));
    list($tjid, $tjiid, $data_item_key) = explode('][', $data['custom_data'], 3);
    $job = Job::load($tjid);
    if (!$job) {
      \Drupal::logger('tmgmt_mygengo')->warning('Failed to load translation job for @data', array('@data' => var_export($data, TRUE)));
      return;
    }

    $remotes = RemoteMapping::loadByLocalData($tjid, $tjiid, $data_item_key);
    $remote = reset($remotes);
    // Create a mapping for this job if we don't have one yet. Should not happen
    // as we pre-create mappings with the order id..
    if (!$remote) {
      $item = JobItem::load($tjiid);
      $item->addRemoteMapping($data_item_key, NULL, array(
        'remote_identifier_2' => $data['job_id'],
        'word_count' => $data['unit_count'],
        'remote_data' => array(
          'credits' => $data['credits'],
          'tier' => $data['tier'],
        ),
        // @todo: Add remote_url.
      ));
    }
    elseif (empty($remote->remote_identifier_2->value)) {
      $remote->remote_identifier_2->value = $data['job_id'];
      $remote->word_count = $data['unit_count'];
      $remote->addRemoteData('credits', $data['credits']);
      $remote->addRemoteData('tier',  $data['tier']);
      $remote->save();
    }

    /**
     * @var \Drupal\tmgmt_mygengo\Plugin\tmgmt\Translator\MyGengoTranslator $mygengo
     */
    $mygengo = $job->getTranslator()->getPlugin();
    // Prepend the job item id.
    $mygengo->saveTranslation($job, $tjiid . '][' . $data_item_key, $data);

    return new Response();
  }
}
