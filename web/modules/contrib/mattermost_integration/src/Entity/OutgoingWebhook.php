<?php

namespace Drupal\mattermost_integration\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\mattermost_integration\OutgoingWebhookInterface;

/**
 * Defines the "outgoing_webhook" entity.
 *
 * @ConfigEntityType(
 *   id = "outgoing_webhook",
 *   label = @Translation("Outgoing Webhook list"),
 *   handlers = {
 *    "list_builder" = "Drupal\mattermost_integration\OutgoingWebhookListBuilder",
 *    "form" = {
 *      "default" = "Drupal\mattermost_integration\Form\OutgoingWebhookForm",
 *      "add" = "Drupal\mattermost_integration\Form\OutgoingWebhookForm",
 *      "edit" = "Drupal\mattermost_integration\Form\OutgoingWebhookForm",
 *      "delete" = "Drupal\mattermost_integration\Form\OutgoingWebhookDeleteForm"
 *     }
 *   },
 *   config_prefix = "outgoing_webhook",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "channel_id" = "channel_id",
 *     "webhook_token" = "webhook_token",
 *     "content_type" = "content_type",
 *     "comment_type" = "comment_type",
 *     "comment_field" = "comment_field",
 *     "convert_markdown" = "convert_markdown",
 *   },
 *   config_prefix = "outgoing_webhook",
 *   admin_permission = "administer site configuration",
 *   links = {
 *     "add-form" = "/admin/structure/webservices/mattermost-integration/outgoing-webhooks/add",
 *     "edit-form" = "/admin/structure/webservices/mattermost-integration/outgoing-webhooks/{outgoing_webhook}/edit",
 *     "delete-form" = "/admin/structure/webservices/mattermost-integration/outgoing-webhooks/{outgoing_webhook}/delete",
 *   }
 * )
 */
class OutgoingWebhook extends ConfigEntityBase implements OutgoingWebhookInterface {
  protected $id;
  protected $channel_id;
  protected $webhook_token;
  protected $content_type;
  protected $comment_type;
  protected $comment_field;
  protected $convert_markdown;

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getChannelId() {
    return $this->channel_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getWebhookToken() {
    return $this->webhook_token;
  }

  /**
   * {@inheritdoc}
   */
  public function getContentType() {
    return $this->content_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommentType() {
    return $this->comment_type;
  }

  /**
   * {@inheritdoc}
   */
  public function getCommentField() {
    return $this->comment_field;
  }

  /**
   * {@inheritdoc}
   */
  public function getConvertMarkdown() {
    return $this->convert_markdown;
  }

}
