<?php

namespace Drupal\applenews;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a channel entity.
 *
 * @code
 *  [createdAt] => 2018-07-27T20:15:08Z
 * [modifiedAt] => 2018-07-27T20:15:34Z
 * [id] => aefc44a9-0c3a-4ca8-82ad-159b362b71d3
 * [type] => channel
 * [shareUrl] => https://apple.news/TrvxEqQw6TKiCrRWbNitx0w
 * [links] => stdClass Object (
 *  [defaultSection] => https://news-api.apple.com/sections/09ef4e89-87a7-4aaf-8184-3d67a5e1f4ac
 *  [self] => https://news-api.apple.com/channels/aefc44a9-0c3a-4ca8-82ad-159b362b71d3
 * )
 * [name] => Playground
 * [website] =>
 * @endcode
 */
interface ChannelInterface extends ContentEntityInterface {

  /**
   * Provides created datetime.
   *
   * @return string
   *   String datetime.
   */
  public function getCreatedAt();

  /**
   * Provides last modified datetime.
   *
   * @return string
   *   String datetime.
   */
  public function getModifiedAt();

  /**
   * Provides channel UUID.
   *
   * @return string
   *   String channel UUID.
   */
  public function getChannelId();

  /**
   * Channel type.
   *
   * @return string
   *   String type.
   */
  public function getType();

  /**
   * Provides url to share for review.
   *
   * @return string
   *   String share URL.
   */
  public function getShareUrl();

  /**
   * List of links.
   *
   * @return string[]
   *   An array of links.
   */
  public function getLinks();

  /**
   * Provides name.
   *
   * @return string
   *   String name of the channel.
   */
  public function getName();

  /**
   * Provides website URL.
   *
   * @return string
   *   String URL.
   */
  public function getWebsite();

}
