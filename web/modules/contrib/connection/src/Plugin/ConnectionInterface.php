<?php

namespace Drupal\connection\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines an interface for Connection plugins.
 */
interface ConnectionInterface extends PluginInspectionInterface {

  /**
   * Return the label of the connection.
   *
   * @return string
   *   returns the label as a string.
   */
  public function getLabel();

  /**
   * Checks the status of the connection defined.
   *
   * @return string
   *   Returns: "Success" or "Error".
   */
  public function getStatus();

  /**
   * Returns request parameters specific to the instance.
   *
   * @param $url
   *   The full base_url and endpoint concatenated.
   *
   * @return mixed
   */
  public function getParams($url);

  /**
   * Sends a request to the API connection.
   *
   * @param $params
   *   The site entity to be used.
   *
   * @return string
   */
  public function request($params);

}
