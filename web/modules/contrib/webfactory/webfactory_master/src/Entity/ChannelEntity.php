<?php

namespace Drupal\webfactory_master\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\webfactory_master\ChannelEntityInterface;

/**
 * Defines the Channel entity.
 *
 * @ConfigEntityType(
 *   id = "channel_entity",
 *   label = @Translation("Channel"),
 *   handlers = {
 *     "list_builder" = "Drupal\webfactory_master\ChannelEntityListBuilder",
 *     "form" = {
 *       "add" = "Drupal\webfactory_master\Form\ChannelEntityForm",
 *       "edit" = "Drupal\webfactory_master\Form\ChannelEntityEditForm",
 *       "delete" = "Drupal\webfactory_master\Form\ChannelEntityDeleteForm"
 *     }
 *   },
 *   config_prefix = "channel_entity",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/services/webfactory/channel_entity/{channel_entity}",
 *     "edit-form" = "/admin/config/services/webfactory/channel_entity/{channel_entity}/edit",
 *     "delete-form" = "/admin/config/services/webfactory/channel_entity/{channel_entity}/delete",
 *     "collection" = "/admin/config/services/webfactory/channel_entity"
 *   }
 * )
 */
class ChannelEntity extends ConfigEntityBase implements ChannelEntityInterface {

  /**
   * The Channel ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Channel label.
   *
   * @var string
   */
  protected $label;

  /**
   * The channel plugin source.
   *
   * @var string
   */
  protected $source;

}
