<?php

namespace Drupal\chat_channels\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Chat channel type entity.
 *
 * @ConfigEntityType(
 *   id = "chat_channel_type",
 *   label = @Translation("Chat channel type"),
 *   handlers = {
 *     "list_builder" = "Drupal\chat_channels\ChatChannelTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\chat_channels\Form\ChatChannelTypeForm",
 *       "edit" = "Drupal\chat_channels\Form\ChatChannelTypeForm",
 *       "delete" = "Drupal\chat_channels\Form\ChatChannelTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\chat_channels\ChatChannelTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "chat_channel_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "chat_channel",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/chat_channel/chat_channel_type/{chat_channel_type}",
 *     "add-form" = "/admin/chat_channel/chat_channel_type/add",
 *     "edit-form" = "/admin/chat_channel/chat_channel_type/{chat_channel_type}/edit",
 *     "delete-form" = "/admin/chat_channel/chat_channel_type/{chat_channel_type}/delete",
 *     "collection" = "/admin/chat_channel/chat_channel_type"
 *   }
 * )
 */
class ChatChannelType extends ConfigEntityBundleBase implements ChatChannelTypeInterface {

  /**
   * The Chat channel type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Chat channel type label.
   *
   * @var string
   */
  protected $label;

}
