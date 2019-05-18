<?php

namespace Drupal\eloqua_app_cloud\Plugin;

/**
 * Base class for Eloqua AppCloud Content Responder plugins.
 *
 */
abstract class EloquaAppCloudContentResponderBase extends EloquaAppCloudInteractiveResponderBase {

  /**
   * Unlike many of the other instantiations the content plugins send back some additional
   * information that Eloqua uses as layout instructions for their UI.
   * Height, Width, and editorImageUrl control what Eloqua shows in a "rendered" html email
   * or landing page preview.
   *
   * @param $instanceId
   * @param null $query
   *
   * @return \stdClass
   */
  public function instantiate($instanceId, $query = NULL){
    $response = parent::instantiate($instanceId, $query);

    $response->height = $this->height();
    $response->width = $this->width();
    $response->editorImageUrl = $this->editorImageUrl();
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function queueWorker() {
    // Return a valid queueWorker for this plugin class.
    return "eloqua_app_cloud_content_queue_worker";
  }

  /**
   * {@inheritdoc}
   */
  public function height() {
    // Retrieve the @height property from the annotation and return it.
    return (string) $this->pluginDefinition['height'];
  }
  /**
   * {@inheritdoc}
   */
  public function width() {
    // Retrieve the @width property from the annotation and return it.
    return (string) $this->pluginDefinition['width'];
  }
  /**
   * {@inheritdoc}
   */
  public function editorImageUrl() {
    // Retrieve the @editorImageUrl property from the annotation and return it.
    return (string) $this->pluginDefinition['editorImageUrl'];
  }
}
