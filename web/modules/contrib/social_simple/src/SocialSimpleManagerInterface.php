<?php

namespace Drupal\social_simple;

use Drupal\social_simple\SocialNetwork\SocialNetworkInterface;

/**
 * Defines an interface a chained service that builds the breadcrumb.
 */
interface SocialSimpleManagerInterface {

  /**
   * Adds another social network builder.
   *
   * @param \Drupal\social_simple\SocialNetwork\SocialNetworkInterface $network
   *   The social network builder to add.
   * @param int $priority
   *   Priority of the social network builder.
   */
  public function addNetwork(SocialNetworkInterface $network, $priority);

  /**
   * Gets the instantiated social network service.
   *
   * @param string $network_id
   *   The network id.
   *
   * @return \Drupal\social_simple\SocialNetwork\SocialNetworkInterface
   *   The social network service.
   */
  public function get($network_id);

  /**
   * Gets all the instantiated social networks.
   *
   * @return array
   *   The social network label keyed by network_id.
   */
  public function getNetworks();

  /**
   * Returns the sorted array of social network objects.
   *
   * @return \Drupal\social_simple\SocialNetwork\SocialNetworkInterface[]
   *   An array of social network objects keyed by their id.
   */
  public function getSortedNetworks();

}
