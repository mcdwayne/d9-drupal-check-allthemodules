<?php

namespace Drupal\lionbridge_translation_provider\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\Entity\RemoteMapping;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Returns responses for lionbridge translation provider routes.
 */
class LionbridgeController extends ControllerBase {

  /**
   * Project complete notifications from Lionbridge.
   */
  public function projectCompleteCallback(Request $request) {
    $secret     = $request->query->get('secret');
    $project_id = $request->request->get('Project')['ProjectID'];

    if (!$secret || !$project_id) {
      throw new AccessDeniedHttpException();
    }

    $remotes = RemoteMapping::loadByRemoteIdentifier($project_id);

    if (empty($remotes)) {
      throw new AccessDeniedHttpException();
    }

    $job = reset($remotes)->getJob();

    if (empty($job->getSetting('secret')) || $job->getSetting('secret') !== $secret) {
      throw new AccessDeniedHttpException();
    }

    $translator = $job->getTranslator();

    if ($translator->getPlugin()->fetchJob($job)) {
      $job_message = 'Translation <a href=":job_link">@job_label</a> is ready for review.';
    }
    else {
      $job_message = 'Attempted to fetch translation job: <a href=":job_link">@job_label</a>.';
    }

    $job->addMessage($job_message, [
      '@job_label' => $job->label(),
      ':job_link' => $job->toUrl()->toString(),
    ]);

    return [];
  }

}
