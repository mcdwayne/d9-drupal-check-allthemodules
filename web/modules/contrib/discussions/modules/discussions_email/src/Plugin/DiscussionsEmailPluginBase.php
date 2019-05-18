<?php

namespace Drupal\discussions_email\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\discussions\Entity\Discussion;
use Drupal\group\Entity\Group;
use Drupal\group\Entity\GroupContent;

/**
 * Provides a base class for discussions email plugins.
 *
 * @see \Drupal\discussions_email\DiscussionsEmailPluginManager
 * @see \Drupal\discussions_email\Plugin\DiscussionsEmailPluginInterface
 * @see plugin_api
 */
abstract class DiscussionsEmailPluginBase extends PluginBase implements DiscussionsEmailPluginInterface {

  const DISCUSSION_GROUP_EMAIL_SEPARATOR = '+';

  /**
   * {@inheritdoc}
   */
  public function processBounce(Group $group, $email) {
    // Only process bounced email if configured to do so.
    $config = \Drupal::config('discussions_email.settings');
    if (!$config->get('process_bounces')) {
      return;
    }

    $user = user_load_by_mail($email);

    if (!empty($user)) {
      $group_member = $group->getMember($user);

      // Set group member to pending status if email bounces.
      if (!empty($group_member)) {
        $group_content = $group_member->getGroupContent();
        $group_content->set('group_requires_approval', 1);
        $group_content->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processUnsubscribe(Group $group, $email) {
    $user = user_load_by_mail($email);

    if (!empty($user)) {
      $group_member = $group->getMember($user);

      if (!empty($group_member)) {
        $group_member->getGroupContent()->delete();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadGroupFromEmail($email) {
    $email_parts = explode('@', $email);

    $email_local_part = explode(self::DISCUSSION_GROUP_EMAIL_SEPARATOR, $email_parts[0]);
    if (isset($email_local_part[0])) {
      $email_username = $email_local_part[0];
    }
    /* Commented out because we're not using for now.
    if (isset($email_local_part[1])) {
    $discussion_id = $email_local_part[1];
    }
    if (isset($email_local_part[2])) {
    $parent_comment_id = $email_local_part[2];
    }
     */
    $group_email = $email_username . '@' . $email_parts[1];

    // Load group using group email.
    $group_ids = \Drupal::entityQuery('group')
      ->condition('discussions_email_address', $group_email, '=')
      ->execute();

    if (!empty($group_ids)) {
      $group_id = current(array_keys($group_ids));

      return Group::load($group_id);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function filterEmailReply($message) {
    // Message content after the reply line (quoted messages) should be removed.
    // Set initial reply line position.
    $reply_line_position = (int) strpos($message, DISCUSSIONS_EMAIL_MESSAGE_SEPARATOR);

    // Some email clients wrap quoted messages in HTML div elements.
    // If configured, locate matching markup in the message.
    $config = \Drupal::config('discussions_email.settings');
    $filter_css_classes = $config->get('filter_css_classes');
    $classes_array = explode(',', $filter_css_classes);

    if (!empty($classes_array)) {
      foreach ($classes_array as $class) {
        $div_tag = '<div class="' . trim($class) . '">';

        // Get position of dev element.
        $tag_pos = strpos($message, $div_tag);
        if ($tag_pos !== FALSE) {

          // If this div element appears in the message body before the
          // previously set reply line position, update the reply line
          // position to this div's position.
          $reply_line_position = ($reply_line_position > 0) ? min($reply_line_position, $tag_pos) : $tag_pos;
        }
      }

      // If reply line position is set, trim from message body.
      if ($reply_line_position > 0) {
        $message = substr($message, 0, $reply_line_position);
      }
    }

    return $message;
  }

  /**
   * {@inheritdoc}
   */
  public function getValidAttachmentFileTypes() {
    /** @var EntityFieldManager $entity_field_manager */
    $entity_field_manager = \Drupal::service('entity_field.manager');

    $comment_fields = $entity_field_manager->getFieldDefinitions('comment', 'discussions_reply');

    $valid_file_types = [];
    if (isset($comment_fields['discussions_attachments'])) {
      /** @var FieldConfig $discussions_attachments */
      $discussions_attachments = $comment_fields['discussions_attachments'];

      $valid_file_extensions = explode(' ', $discussions_attachments->getSetting('file_extensions'));
      $valid_file_types = [];

      /** @var MimeTypeGuesser $mime_type_guesser */
      $mime_type_guesser = \Drupal::service('file.mime_type.guesser');

      // Create an array of valid file types based on valid extensions.
      foreach ($valid_file_extensions as $ext) {
        // MimeTypeGuesser requires a full file name, but the file doesn't
        // need to exist.
        $fake_path = 'attachment.' . $ext;
        $valid_file_types[] = $mime_type_guesser->guess($fake_path);
      }
    }

    return $valid_file_types;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewDiscussion(AccountInterface $user, Group $group, $message) {
    // Get first enabled group_discussion plugin to create discussion
    // group content.
    // TODO: Allow a way to indicate which plugin to use from email address?
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $default_plugin */
    $default_plugin = NULL;
    /** @var \Drupal\group\Plugin\GroupContentEnablerInterface $plugin */
    foreach ($group->getGroupType()->getInstalledContentPlugins() as $plugin_id => $plugin) {
      if ($plugin->getBaseId() == 'group_discussion') {
        $default_plugin = $plugin;
        break;
      }
    }

    list($plugin_type, $discussion_type) = explode(':', $default_plugin->getPluginId());

    $discussion = Discussion::create([
      'type' => $discussion_type,
      'uid' => $user->id(),
      'subject' => $message['subject'],
    ]);

    // Set message ID for discussion to the message ID passed in the
    // headers to maintain threading for the person who created the discussion
    // by sending an email to the group email address.
    // This if statement should only be hit when a discussion is created by
    // sending an email to the discussion group email address.
    if (isset($message['headers']['Message-Id'])) {
      $discussion->set(DISCUSSIONS_EMAIL_MESSAGE_ID_FIELD, $message['headers']['Message-Id']);
    }

    if ($discussion->save() == SAVED_NEW) {
      $group_content = GroupContent::create([
        'type' => $default_plugin->getContentTypeConfigId(),
        'gid' => $group->id(),
      ]);

      $group_content->set('entity_id', $discussion->id());
      $group_content->save();
    }

    return $discussion;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    // Merge in the default configuration.
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
