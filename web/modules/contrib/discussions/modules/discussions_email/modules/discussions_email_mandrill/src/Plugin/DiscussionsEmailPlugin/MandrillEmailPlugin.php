<?php

namespace Drupal\discussions_email_mandrill\Plugin\DiscussionsEmailPlugin;

use Drupal\comment\Entity\Comment;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\discussions\Entity\Discussion;
use Drupal\discussions_email\Plugin\DiscussionsEmailPluginBase;
use Drupal\file\Entity\File;
use Drupal\group\Entity\Group;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides a content enabler for users.
 *
 * @DiscussionsEmailPlugin(
 *   id = "discussions_email_mandrill",
 *   label = @Translation("Mandrill Email Plugin for Discussions"),
 *   description = @Translation("Allows discussions to be sent via email using Mandrill.")
 * )
 */
class MandrillEmailPlugin extends DiscussionsEmailPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getInboundDomains() {
    /** @var \Drupal\mandrill\MandrillAPI $api */
    $api = \Drupal::service('mandrill.api');

    return $api->getInboundDomains();
  }

  /**
   * Gets the first active webhook matching this site's URL.
   *
   * @return array
   *   Array of Mandrill webhook data.
   */
  public function getActiveWebhook() {
    global $base_url;

    /** @var MandrillAPI $mandrill */
    $mandrill = \Drupal::service('mandrill.api');

    $webhooks = $mandrill->getWebhooks();

    $url = $base_url . '/discussions/email/webhook' . (isset($_REQUEST['domain']) ? '?domain=' . $_REQUEST['domain'] : '');

    foreach ($webhooks as $webhook) {
      if ($webhook['url'] == $url) {
        return $webhook;
      }
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validateWebhookSource() {
    global $base_url;

    // @see http://help.mandrill.com/entries/23704122-Authenticating-webhook-requests
    if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
      return TRUE;
    }

    if (!isset($_POST)) {
      return FALSE;
    }

    $webhook = $this->getActiveWebhook();
    if (!$webhook || !$webhook['auth_key']) {
      return FALSE;
    }

    $auth_key = $webhook['auth_key'];

    ksort($_POST);

    $url = $base_url . $_SERVER['REQUEST_URI'];
    foreach ($_POST as $arg => $val) {
      $url .= $arg . $val;
    }

    $signature = base64_encode(hash_hmac('sha1', $url, $auth_key, TRUE));

    return ($signature == $_SERVER['HTTP_X_MANDRILL_SIGNATURE']);
  }

  /**
   * {@inheritdoc}
   */
  public function processWebhook(array $data) {
    // Return an empty response if the webhook is being verified.
    if ($_SERVER['REQUEST_METHOD'] == 'HEAD') {
      return Response::create();
    }

    // Get Mandrill events from webhook data.
    $events = Json::decode($_POST['mandrill_events']);

    // Process Mandrill events.
    foreach ($events as $event) {
      switch ($event['event']) {
        case 'hard_bounce':
        case 'reject':
          /** @var \Drupal\group\Entity\Group $group */
          $group = $this->loadGroupFromEmail($event['msg']['email']);

          if (!empty($group)) {
            $this->processBounce($group, $event['msg']['from_email']);
          }
          else {
            \Drupal::logger('discussions_email_mandrill')->error('Unable to process reject message; no group found for email {email}', [
              'email' => $event['msg']['email'],
            ]);
          }
          break;

        case 'inbound':
          $this->processMessage($event['msg']);
          break;
      }
    }

    return Response::create(count($events) . ' events processed.');
  }

  /**
   * {@inheritdoc}
   */
  public function sendEmail(array $message, Group $group = NULL, Discussion $discussion = NULL, Comment $comment = NULL) {
    $group_email_address = $group->get('discussions_email_address')->value;
    $group_owner_email_address = $group->getOwner()->getEmail();

    // Convert message body to array for Mandrill.
    $message['body'] = [$message['body']];

    $message['params'] = [
      'mandrill' => [
        // Move 'from_email' and 'from_name' to suit Mandrill mail plugin.
        'from_email' => $message['from_email'],
        'from_name' => $message['from_name'],
        'overrides' => [
          'preserve_recipients' => FALSE,
        ],
      ],
    ];

    // Add Mandrill headers.
    $message['params']['mandrill']['header'] = [
      'Message-Id' => $message['id'],
      'Precedence' => 'List',
      // Mandrill currently only allows List-Help, but this may change.
      'List-Help' => "<mailto:{$group_owner_email_address}>",
      'List-Unsubscribe:' => "<mailto:{$group_email_address}?subject=unsubscribe>",
      'List-Post:' => "<mailto:{$group_email_address}>",
      'List-Owner:' => "<mailto:{$group_owner_email_address}>",
    ];

    // Pass In-Reply-To header to maintain threading in the inboxes' of our
    // discussion group members.
    if (!empty($message['in_reply_to'])) {
      $message['params']['mandrill']['header']['In-Reply-To'] = $message['in_reply_to'];
    }

    // Add attachments.
    $attachments = $comment->get('discussions_attachments')->getValue();

    if (!empty($attachments)) {
      $message['attachments'] = [];

      $config = \Drupal::config('discussions_email.settings');
      // Convert attachment send limit from MB to bytes.
      $attachment_send_limit = ($config->get('attachment_send_limit') * 1000000);

      foreach ($attachments as $attachment) {
        /** @var \Drupal\file\Entity\File $file */
        $file = File::load($attachment['target_id']);

        if ($file->getSize() < $attachment_send_limit) {
          // Attach files under send limit to email.
          $message['attachments'][] = $file->getFileUri();
        }
        else {
          // Add link to files over send limit.
          $file_url = Url::fromUri(file_create_url($file->getFileUri()));
          $message['body'][] = $file_url->toString();
        }
      }
    }

    // Concatenate recipient email addresses for Mandrill mail plugin.
    $message['to'] = implode(',', $message['to']);

    // 'module' value must be set to avoid error in Mandrill mail plugin.
    $message['module'] = 'discussions_email';

    /** @var MailManager $mail_manager */
    $mail_manager = \Drupal::service('plugin.manager.mail');

    /** @var MandrillMail $mandrill */
    $mandrill = $mail_manager->createInstance('mandrill_mail');

    // Format and send mail through Mandrill.
    $message['html'] = $message['body'];

    $message = $mandrill->format($message);
    return $mandrill->mail($message);
  }

  /**
   * Processes the message.
   *
   * @param mixed $message
   *   Associative array of message information.
   *   - email (string): The recipient email address in the format:
   *     {string}+{int}+{int}@domain.tld
   *       - Group email username (string).
   *       - Discussion ID (int) (optional).
   *       - Parent comment ID (int) (optional).
   *
   * @return bool
   *   TRUE if message was successfully processed, FALSE otherwise.
   */
  public function processMessage($message) {
    // TODO: Ignore messages sent from the discussions_email module.
    // Load user using the message sender's email address.
    /** @var AccountInterface $user */
    $user = user_load_by_mail($message['from_email']);

    if (empty($user)) {
      \Drupal::logger('discussions_email_mandrill')->error('Unable to process message; no user found with email {email}', [
        'email' => $message['from_email'],
      ]);
      return FALSE;
    }

    // Load discussion group from group email address.
    /** @var \Drupal\group\Entity\Group $group */
    $group = $this->loadGroupFromEmail($message['email']);

    if (empty($group)) {
      \Drupal::logger('discussions_email_mandrill')->error('Unable to process message; no group found for email {email}', [
        'email' => $message['email'],
      ]);
      return FALSE;
    }

    // Check user is a valid member of the group.
    $membership = $group->getMember($user);
    if (!$membership || $membership->requiresApproval()) {
      return FALSE;
    }

    // Process unsubscribe message.
    if (trim($message['subject']) == 'unsubscribe') {
      $this->processUnsubscribe($group, $message['from_email']);
      return TRUE;
    }

    // TODO: Check user permission to create reply to discussion.
    // TODO: Can group / posting access be done in DiscussionsEmailPluginBase?
    $email_parts = explode('@', $message['email']);
    list($email_username, $discussion_id, $parent_comment_id) = explode(self::DISCUSSION_GROUP_EMAIL_SEPARATOR, $email_parts[0]);

    /** @var \Drupal\discussions\GroupDiscussionService $group_discussion_service */
    $group_discussion_service = \Drupal::service('discussions.group_discussion');

    if (!empty($discussion_id)) {
      // Load discussion.
      $discussion = $group_discussion_service->getGroupDiscussion($group->id(), $discussion_id);

      if (empty($discussion)) {
        \Drupal::logger('discussions_email_mandrill')->error('Unable to process message; no discussion with ID {discussion_id} found in group with ID {group_id}', [
          'discussion_id' => $discussion_id,
          'group_id' => $group->id(),
        ]);
        return FALSE;
      }
    }
    else {
      // Create new discussion.
      $discussion = $this->createNewDiscussion($user, $group, $message);
    }

    if (!empty($discussion)) {
      // Create file attachments.
      // @see https://mandrill.zendesk.com/hc/en-us/articles/205583207-What-is-the-format-of-inbound-email-webhooks
      $files = [];
      if (isset($message['attachments'])) {
        /** @var EntityFieldManager $entity_field_manager */
        $entity_field_manager = \Drupal::service('entity_field.manager');

        $comment_fields = $entity_field_manager->getFieldDefinitions('comment', 'discussions_reply');

        if (isset($comment_fields['discussions_attachments'])) {
          /** @var FieldConfig $discussions_attachments */
          $discussions_attachments = $comment_fields['discussions_attachments'];

          $file_directory = $discussions_attachments->getSetting('file_directory');

          $valid_file_types = $this->getValidAttachmentFileTypes();

          // Build file path.
          $base_path = explode('/', $file_directory)[0] . '/' . strtolower(str_replace(' ', '_', $discussion->label()));

          // Save attachments to file system.
          foreach ($message['attachments'] as $filename => $attachment) {
            // Ignore invalid file types.
            if (!in_array($attachment['type'], $valid_file_types)) {
              continue;
            }

            $uri = file_build_uri($base_path);
            file_prepare_directory($uri, FILE_CREATE_DIRECTORY && FILE_MODIFY_PERMISSIONS);
            $file = file_save_data(base64_decode($attachment['content']), $uri . '/' . $attachment['name']);

            $files[] = $file;
          }
        }
      }

      // Add comment.
      $filtered_message = $this->filterEmailReply($message['html']);

      $group_discussion_service->addComment($discussion->id(), $parent_comment_id, $user->id(), $filtered_message, $files);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    // TODO: Any Mandrill plugin configuration.
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // TODO: Any Mandrill plugin configuration fields.
    return $form;
  }

}
