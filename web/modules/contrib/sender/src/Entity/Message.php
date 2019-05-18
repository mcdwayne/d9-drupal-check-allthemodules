<?php

namespace Drupal\sender\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Session\AccountInterface;

/**
 * A message that can be sent using Sender.
 *
 * @ConfigEntityType(
 *   id = "sender_message",
 *   label = @Translation("Message"),
 *   admin_permission = "administer sender_message",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "group",
 *     "subject",
 *     "body",
 *     "tokenTypes",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\sender\Entity\MessageListBuilder",
 *     "access" = "Drupal\entity\EntityAccessControlHandler",
 *     "permission_provider" = "Drupal\entity\EntityPermissionProvider",
 *     "local_action_provider" = {
 *       "Drupal\entity\Menu\EntityCollectionLocalActionProvider",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\sender\Form\MessageForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *   },
 *   links = {
 *     "canonical" = "/admin/config/system/sender/messages/{sender_message}",
 *     "collection" = "/admin/config/system/sender/messages",
 *     "add-form" = "/admin/config/system/sender/messages/add",
 *     "edit-form" = "/admin/config/system/sender/messages/{sender_message}/edit",
 *     "delete-form" = "/admin/config/system/sender/messages/{sender_message}/delete",
 *   }
 * )
 */
class Message extends ConfigEntityBase implements MessageInterface {

  const ID_MAX_LENGTH = 64;

  /**
   * The message's ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The message's label.
   *
   * @var string
   */
  protected $label;

  /**
   * The message's group ID.
   *
   * @var string
   */
  protected $group;

  /**
   * The message's subject.
   *
   * @var string
   */
  protected $subject;

  /**
   * The message's body, containing 'value' and 'format' keys.
   *
   * @var array
   */
  protected $body;

  /**
   * Token types allowed to be used in the subject or body.
   *
   * @var array
   */
  protected $tokenTypes = [];

  /**
   * {@inheritdoc}
   */
  public function build(AccountInterface $recipient, array $data = []) {
    // The recipient is passed along to the token replacement.
    $data['sender_recipient'] = $recipient;

    // Tokens are replaced in the message's subject and body.
    $token_service = \Drupal::token();

    // Replaces tokens in the message's subject and body.
    $options = ['clear' => TRUE];
    $subject = isset($this->subject) ? $token_service->replace($this->subject, $data, $options) : '';
    $bodyValue = isset($this->body['value']) ? $token_service->replace($this->body['value'], $data, $options) : '';

    // Builds a render array for the message.
    $render_array = [
      '#theme' => 'sender_message',
      '#subject' => $subject,
      '#body_text' => $bodyValue,
      '#body_format' => $this->getBodyFormat(),
      '#message_id' => $this->id(),
    ];

    return $render_array;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label();
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->label = $label;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId() {
    return $this->group ?: '';
  }

  /**
   * {@inheritdoc}
   */
  public function setGroupId($group_id) {
    return $this->group = $group_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    if (!empty($this->group)) {
      // Loads the plugin.
      $plugin_manager = \Drupal::service('plugin.manager.sender_message_groups');
      $plugin = $plugin_manager->createInstance($this->group);
    }
    return isset($plugin) ? $plugin : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getTokenTypes() {
    // If the group is defined, the token types are taken from it. Otherwise, 
    // they are taken from this message's setting.
    $token_types = [];
    if ($group_plugin = $this->getGroup()) {
      // Group's token types.
      $token_types = $group_plugin->getTokenTypes();
    }
    else {
      // Message's token types.
      $token_types = $this->tokenTypes;
    }

    // Ensures the "sender-recipient" token is included.
    if (!in_array('sender-recipient', $token_types)) {
      $token_types[] = 'sender-recipient';
    }
    return $token_types;
  }

  /**
   * {@inheritdoc}
   */
  public function setTokenTypes(array $token_types) {
    $this->tokenTypes = $token_types;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubject() {
    return isset($this->subject) ? $this->subject : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSubject($subject) {
    $this->subject = $subject;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyFormat() {
    return isset($this->body['format']) ? $this->body['format'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBodyValue() {
    return isset($this->body['value']) ? $this->body['value'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBody() {
    $value = [
      'value' => $this->getBodyValue(),
      'format' => $this->getBodyFormat(),
    ];
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBody(array $body) {
    $this->body = $body + ['value' => '', 'format' => 'full_html'];
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function loadByGroup($group_id) {
    $query = \Drupal::entityQuery('sender_message');
    $query->condition('group', $group_id);
    if ($entity_ids = $query->execute()) {
      $entities = static::loadMultiple($entity_ids);
    }
    return $entities ?: [];
  }

}
