<?php

namespace Drupal\tmgmt_smartling\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmgmt\Entity\Job;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;

class PushCallbackController extends ControllerBase {

  public function callback(Request $request) {
    // Check if we have a job.
    if (!($request->get('job'))) {
      throw new NotFoundHttpException();
    }

    // Check if we have a job.
    if (!($request->get('fileUri')) || !($request->get('locale'))) {
      throw new NotFoundHttpException();
    }

    $job = Job::load($request->get('job'));

    if (!$job) {
      throw new NotFoundHttpException();
    }

    tmgmt_smartling_download_file($job);

    return new Response('OK');
  }

}
