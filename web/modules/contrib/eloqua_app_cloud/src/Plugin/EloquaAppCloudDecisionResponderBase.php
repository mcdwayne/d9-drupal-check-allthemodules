<?php

namespace Drupal\eloqua_app_cloud\Plugin;

/**
 * Base class for Eloqua AppCloud Decision Responder plugins.
 */
abstract class EloquaAppCloudDecisionResponderBase extends EloquaAppCloudInteractiveResponderBase {

  /**
   * {@inheritdoc}
   */
  public function queueWorker() {
    // Return a valid queueWorker for this plugin class.
    return "eloqua_app_cloud_decision_queue_worker";
  }
}
