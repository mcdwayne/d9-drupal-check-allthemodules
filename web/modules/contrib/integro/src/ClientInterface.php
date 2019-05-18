<?php

namespace Drupal\integro;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the base interface for integro client plugins.
 */
interface ClientInterface extends PluginInspectionInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * Prepares the authorization.
   *
   * @return mixed
   */
  public function authPrepare();

  /**
   * Authorizes the client.
   *
   * @return mixed
   */
  public function auth();

  /**
   * Handles the client authorization result.
   *
   * @param array $result
   * @return mixed
   */
  public function authHandle(array $result);

  /**
   * Prepares the request.
   *
   * @return mixed
   */
  public function requestPrepare();

  /**
   * Sends the request.
   *
   * @return mixed
   */
  public function request();

  /**
   * Handles the request result.
   *
   * @param mixed $result
   * @return mixed
   */
  public function requestHandle($result);

  /**
   * Gets the label.
   *
   * @return mixed
   *   The label.
   */
  public function getLabel();

}
