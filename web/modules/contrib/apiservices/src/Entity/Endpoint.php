<?php

/**
 * @file
 * Contains \Drupal\apiservices\Entity\Endpoint.
 */

namespace Drupal\apiservices\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Stores the configuration for an API endpoint.
 *
 * @ConfigEntityType(
 *   id = "apiservices_endpoint",
 *   label = @Translation("API endpoint"),
 *   config_prefix = "endpoint",
 *   entity_keys = {
 *     "id" = "id"
 *   }
 * )
 */
class Endpoint extends ConfigEntityBase implements EndpointInterface {

  /**
   * The endpoint ID.
   *
   * @var string
   */
  protected $id;

  /**
   * A list of placeholders in the endpoint path that must be replaced.
   *
   * @var array
   */
  protected $arguments = [];

  /**
   * The endpoint display name.
   *
   * @var string
   */
  protected $name;

  /**
   * The API endpoint location, which will be appended to the API host that is
   * managed by the endpoint provider that is used.
   *
   * @var string
   */
  protected $path;

  /**
   * The name of a default ApiProviderInterface implementation that can be used
   * to build a request for this endpoint.
   *
   * @var string
   */
  protected $provider;

  /**
   * A list of query parameters supported by this endpoint.
   *
   * @var array
   */
  protected $query = [];

  /**
   * {@inheritdoc}
   */
  public function getArguments() {
    return $this->arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getQueryParameters() {
    return $this->query;
  }

}
