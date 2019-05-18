<?php

namespace Drupal\pusher_integration\Entity;

/**
 * @file
 * Contains \Drupal\pusher_integration\Entity\ChannelPathMap.
 */

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\pusher_integration\ChannelPathMapInterface;

/**
 * Defines the ChannelPathMap entity.
 *
 * @ConfigEntityType(
 *   id = "channel_path_map",
 *   label = @Translation("Channel Path Map Entry"),
 *   handlers = {
 *     "list_builder" = "Drupal\pusher_integration\Controller\ChannelPathMapListBuilder",
 *     "form" = {
 *       "add" = "Drupal\pusher_integration\Form\ChannelPathMapForm",
 *       "delete" = "Drupal\pusher_integration\Form\ChannelPathMapDeleteForm",
 *       "edit" = "Drupal\pusher_integration\Form\ChannelPathMapForm",
 *     }
 *   },
 *   config_prefix = "channel_path_map",
 *   admin_permission = "administer site configuration",
 *   list_cache_tags = {
 *    "rendered"
 *   },
 *   entity_keys = {
 *     "id" = "mapId",
 *   },
 *   config_export = {
 *     "mapId",
 *     "channelName",
 *     "pathPattern"
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/pusher_integration/{id}/delete",
 *     "edit-form" = "/admin/config/pusher_integration/{id}/edit"
 *   }
 * )
 */
class ChannelPathMap extends ConfigEntityBase implements ChannelPathMapInterface {

  public $mapId;
  protected $channelName;
  protected $pathPattern;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->mapId;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapId() {
    return $this->mapId;
  }

  /**
   * {@inheritdoc}
   */
  public function setMapId($mapId) {
    $this->mapId = $mapId;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelName() {
    return $this->channelName;
  }

  /**
   * {@inheritdoc}
   */
  public function setChannelName($channelName) {
    $this->channelName = $channelName;
  }

  /**
   * {@inheritdoc}
   */
  public function getPathpattern() {
    return $this->pathPattern;
  }

  /**
   * {@inheritdoc}
   */
  public function setPathPattern($pathPattern) {
    $this->pathPattern = $pathPattern;
  }

}
