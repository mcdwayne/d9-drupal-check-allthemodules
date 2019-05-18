<?php

namespace Drupal\eloqua_app_cloud\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Eloqua AppCloud Interactive Responder plugins.
 */
interface EloquaAppCloudInteractiveResponderInterface extends PluginInspectionInterface {


  /**
   * Method gets called when a create call is sent by Eloqua.
   *
   * @param $instanceId
   *
   * @param $query
   *
   * @return mixed
   */
  public function instantiate($instanceId, $query);

  /**
   * Method gets called when a configure (update) call is sent by Eloqua.
   *
   * @param $instanceId
   *
   * @param $query
   *  The entire query string from whence to pluck additional values if available.
   *
   * @return mixed
   */
  public function update($instanceId, $query);

  /**
   * Method gets called by nightly delete batch from Eloqua.
   *
   * @param $instanceId
   *
   * @param $query
   *  The entire query string from whence to pluck additional values if available.
   *
   * @return mixed
   */
  public function delete($instanceId, $query);

  /**
   * Method that gets executed when a Interactive Service is invoked from
   * within the Eloqua UI.
   *
   * @param $instanceId
   * @param object $record
   * object jsondecoded from Eloqua transmission.
   *
   * @param $query
   *   The entire query string from whence to pluck additional values if available.
   *
   * @return null
   */
  public function execute($instanceId, $record, $query);

  /**
   * @return array
   *    The list of fields this plugin needs from Eloqua.
   */
  public function fieldList();

  /**
   * @return string
   *    The API type (contacts or customObject)
   */
  public function api();

  /**
   * @return string
   *     The name of the queue worker plugin this plugin requires.
   */
  public function queueWorker();

  /**
   * @return string
   *     The type of response this plugin requires (i.e. synchronous or
   *   asynchronous).
   */
  public function respond();

  /**
   * @return string
   *     The label of this plugin. Will be used as page title for configure
   *   calls from Eloqua
   */
  public function label();

  /**
   * @return string
   *     The description of this plugin. Will be used for configure calls from
   *   Eloqua
   */
  public function description();

  /**
   * @return string
   *
   * If true than Eloqua will require a call to the update endpoint, and the response must indicate
   * requiresConfiguration = FALSE before a canvas can be activated.
   * The annotation defines a boolean, but this
   * function needs to return a string in the format "true" or "false"
   */
  public function requiresConfiguration();

}
