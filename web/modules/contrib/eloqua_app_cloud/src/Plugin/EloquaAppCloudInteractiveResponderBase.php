<?php

namespace Drupal\eloqua_app_cloud\Plugin;

/**
 * Base class for Eloqua AppCloud Interactive Responder plugins.
 *
 */
abstract class EloquaAppCloudInteractiveResponderBase extends EloquaAppCloudResponderBase implements EloquaAppCloudInteractiveResponderInterface {

  // Add common methods and abstract methods for your plugin type here.


  /**
   * @param $instanceId
   * @param $query
   *
   * @return \stdClass
   */
  public function instantiate($instanceId, $query = NULL){

    $response = new \stdClass();
    $response->recordDefinition = $this->fieldList();
    $response->requiresConfiguration = $this->requiresConfiguration();
    return $response;
  }

  /**
   * Return a default string that gets rendered in the Eloqua canvas interface. This should be overridden in plugin to return a form if the plugin is configurable.
   *
   * @param $instanceId
   * @param $query
   *
   * @return mixed|string
   */
  public function update($instanceId, $query = NULL) {
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * Handle any task required to cleanly delete this plugin.
   * @param $instanceID
   * @param $query
   */
  public function delete($instanceID, $query = NULL) {

  }

  /**
   * {@inheritdoc}
   */
  public function api() {
    // Retrieve the @api property from the annotation and return it.
    return (string) $this->pluginDefinition['api'];
  }
  /**
   * {@inheritdoc}
   */
  public function fieldList() {
    // Retrieve the @fieldList property from the annotation and return it.
    return (array) $this->pluginDefinition['fieldList'];
  }

  /**
   * {@inheritdoc}
   */
  public function respond() {
    // Retrieve the @respond property from the annotation and return it.
    return (string) $this->pluginDefinition['respond'];
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Retrieve the @description property from the annotation and return it.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    // Retrieve the @label property from the annotation and return it.
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function requiresConfiguration() {
    return (string) $this->pluginDefinition['requiresConfiguration'] ? "true" : "false";
  }

}
