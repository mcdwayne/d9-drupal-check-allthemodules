<?php

namespace Drupal\mattermost_integration;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Outgoing Webhooks.
 *
 * @package Drupal\mattermost_integration
 */
interface OutgoingWebhookInterface extends ConfigEntityInterface {

  /**
   * Gets the identifier of this webhook.
   *
   * @return int
   *   The identifier of this webhook.
   */
  public function getId();

  /**
   * Gets the Mattermost channel identifier associated with this webhook.
   *
   * @return string
   *   The Mattermost channel id.
   */
  public function getChannelId();

  /**
   * Gets the outgoing webhook token.
   *
   * @return string
   *   The webhook token.
   */
  public function getWebhookToken();

  /**
   * Get the content type this webhook creates.
   *
   * @return string
   *   The content type.
   */
  public function getContentType();

  /**
   * Get the comment type this webhook creates.
   *
   * @return string
   *   The comment type.
   */
  public function getCommentType();

  /**
   * Get the comment field name from the target node type.
   *
   * @return string
   *   The comment field name.
   */
  public function getCommentField();

  /**
   * Whether or not to convert incoming Markdown to HTML.
   *
   * @return bool
   *   True if markdown should be converted, false to leave the Markdown alone.
   */
  public function getConvertMarkdown();

}
