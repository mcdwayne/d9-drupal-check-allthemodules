<?php

namespace Drupal\discussions_email\Plugin;

use Drupal\comment\Entity\Comment;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\discussions\Entity\Discussion;
use Drupal\group\Entity\Group;

/**
 * Defines the interface for discussions email plugins.
 *
 * @see \Drupal\discussions_email\Annotation\DiscussionsEmailPlugin
 * @see \Drupal\discussions_email\DiscussionsEmailPluginManager
 * @see \Drupal\discussions_email\Plugin\DiscussionsEmailPluginBase
 * @see plugin_api
 */
interface DiscussionsEmailPluginInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Gets an array of inbound domains from the email provider.
   *
   * @return array
   *
   *   TODO: Standardize format for response between plugins.
   */
  public function getInboundDomains();

  /**
   * Validates the source of an update sent to the webhook endpoint.
   *
   * @return bool
   *   TRUE if the source is valid, FALSE otherwise.
   */
  public function validateWebhookSource();

  /**
   * Process data received from the email provider via a webhook update.
   *
   * @param array $data
   *   Data received via webhook request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   HTTP response object.
   */
  public function processWebhook(array $data);

  /**
   * Process a bounced email message.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The discussion group the email message originated from.
   * @param string $email
   *   The email address a bounced message notification originated from.
   */
  public function processBounce(Group $group, $email);

  /**
   * Process an unsubscribe request.
   *
   * @param \Drupal\group\Entity\Group $group
   *   The discussion group to unsubscribe from.
   * @param string $email
   *   The email address to unsubscribe.
   */
  public function processUnsubscribe(Group $group, $email);

  /**
   * Process an email message and creates or updates a discussion.
   *
   * @param mixed $message
   *   The email message.
   *   Each discussion email plugin provides its own data structure.
   *
   * @return bool
   *   TRUE if message was successfully processed, FALSE otherwise.
   */
  public function processMessage($message);

  /**
   * Loads a discussion group using the group's email address.
   *
   * @param string $email
   *   An email address in the format:
   *   {string}+{int}+{int}@domain.tld
   *     - Group email username (string).
   *     - Discussion ID (int) (optional).
   *     - Parent comment ID (int) (optional).
   *
   * @return \Drupal\group\Entity\GroupInterface
   *   The group object.
   */
  public function loadGroupFromEmail($email);

  /**
   * Removes HTML markup from email messages based on group configuration.
   *
   * @param string $message
   *   The original email message.
   *
   * @return string
   *   The filtered email message.
   */
  public function filterEmailReply($message);

  /**
   * Gets an array of file MIME types valid for use as email attachments.
   *
   * @return array
   *   Array of valid MIME types as strings.
   */
  public function getValidAttachmentFileTypes();

  /**
   * Creates a new discussion in a group.
   *
   * @param \Drupal\Core\Session\AccountInterface $user
   *   The user creating the discussion.
   * @param \Drupal\group\Entity\Group $group
   *   The group to create the discussion in.
   * @param string $subject
   *   The discussion subject.
   *
   * @return \Drupal\discussions\Entity\Discussion
   *   The new discussion entity.
   */
  public function createNewDiscussion(AccountInterface $user, Group $group, $subject);

  /**
   * Sends an email message.
   *
   * @param array $message
   *   Associative array of message data with the following keys:
   *   - id (string) The message ID.
   *   - from_name (string) The sender name.
   *   - from_email (string) The sender email address.
   *   - to (array) Recipient email addresses.
   *   - subject (string) The message subject.
   *   - body (string) The message body.
   * @param \Drupal\group\Entity\Group $group
   *   The discussion group containing the recipients.
   * @param \Drupal\discussions\Entity\Discussion $discussion
   *   The discussion the email message originated from.
   * @param \Drupal\comment\Entity\Comment $comment
   *   The discussion comment contained in this email.
   */
  public function sendEmail(array $message, Group $group = NULL, Discussion $discussion = NULL, Comment $comment = NULL);

}
