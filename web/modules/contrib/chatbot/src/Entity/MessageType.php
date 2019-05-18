<?php

namespace Drupal\chatbot\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Message type entity.
 *
 * @ConfigEntityType(
 *   id = "chatbot_message_type",
 *   label = @Translation("Message type"),
 *   handlers = {
 *     "list_builder" = "Drupal\chatbot\MessageTypeListBuilder",
 *     "form" = {
 *       "add" = "Drupal\chatbot\Form\MessageTypeForm",
 *       "edit" = "Drupal\chatbot\Form\MessageTypeForm",
 *       "delete" = "Drupal\chatbot\Form\MessageTypeDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\chatbot\MessageTypeHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "chatbot_message_type",
 *   admin_permission = "administer site configuration",
 *   bundle_of = "chatbot_message",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/chatbots/chatbot_message_type/{chatbot_message_type}",
 *     "add-form" = "/admin/structure/chatbots/chatbot_message_type/add",
 *     "edit-form" = "/admin/structure/chatbots/chatbot_message_type/{chatbot_message_type}/edit",
 *     "delete-form" = "/admin/structure/chatbots/chatbot_message_type/{chatbot_message_type}/delete",
 *     "collection" = "/admin/structure/chatbots/chatbot_message_type"
 *   }
 * )
 */
class MessageType extends ConfigEntityBundleBase implements MessageTypeInterface {

  /**
   * The Message type ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Message type label.
   *
   * @var string
   */
  protected $label;

}
