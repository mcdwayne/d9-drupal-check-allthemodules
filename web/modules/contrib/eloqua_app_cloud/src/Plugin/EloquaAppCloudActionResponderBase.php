<?php

namespace Drupal\eloqua_app_cloud\Plugin;

/**
 * Base class for Eloqua AppCloud Action Responder plugins.
 */
abstract class EloquaAppCloudActionResponderBase extends EloquaAppCloudInteractiveResponderBase {

  /**
   * {@inheritdoc}
   */
  public function queueWorker() {
    // Return a valid queueWorker for this plugin class.
    return "eloqua_app_cloud_action_queue_worker";
  }
}
