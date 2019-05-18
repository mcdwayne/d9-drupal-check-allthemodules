<?php

/**
 * @file
 * Contains \Drupal\apiservices\Entity\EndpointInterface.
 */

namespace Drupal\apiservices\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for getting basic endpoint information.
 */
interface EndpointInterface extends ConfigEntityInterface {

  /**
   * Gets a list of placeholders that are used in the endpoint path.
   *
   * @return array
   *   A list of placeholders that must be specified in order to build a
   *   complete API request from the endpoint path.
   */
  public function getArguments();

  /**
   * Gets the display name of this endpoint.
   *
   * @return string
   *   The enpoint display name.
   */
  public function getName();

  /**
   * Gets the endpoint path, which may include placeholders.
   *
   * @return string
   *   The URL path to use when building an API request.
   */
  public function getPath();

  /**
   * Gets the name of a default ApiProviderInterface implementation that can be
   * used to build a request for this endpoint.
   *
   * @return string
   *   The name of an API provider class.
   */
  public function getProvider();

  /**
   * Gets a list of query parameters supported by this endpoint.
   *
   * Each parameter may have an additional list of allowed values.
   *
   * @return array
   *   A list of optional query parameters that may be specified in the API
   *   request for this endpoint.
   */
  public function getQueryParameters();

}
