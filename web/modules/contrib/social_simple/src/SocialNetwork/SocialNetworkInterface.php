<?php

namespace Drupal\social_simple\SocialNetwork;

use Drupal\Core\Entity\EntityInterface;

/**
 * Defines the interface for social network.
 *
 * Allows for each social network to build its shared url, and to add
 * additionnal options to the url built.
 */
interface SocialNetworkInterface {

  /**
   * Checks whether the given transition is allowed.
   *
   * @param string $share_url
   *   The url to share.
   * @param string $title
   *   The page's title to share.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The parent entity.
   * @param array $additional_options
   *   Additional options to pass as que query parameter to the social link.
   *
   * @return array
   *   the renderable array of the social share link.
   */
  public function getShareLink($share_url, $title = '', EntityInterface $entity = NULL, array $additional_options = []);

  /**
   * Get the network name.
   *
   * @return string
   *   the network name.
   */
  public function getLabel();

  /**
   * Get the network id.
   *
   * @return string
   *   the network id.
   */
  public function getId();

  /**
   * Get common attributes for the share link.
   *
   * @param string $network_name
   *   The social network name.
   *
   * @return array $attributes
   *   an array of link attributes.
   */
  public function getLinkAttributes($network_name);

}
