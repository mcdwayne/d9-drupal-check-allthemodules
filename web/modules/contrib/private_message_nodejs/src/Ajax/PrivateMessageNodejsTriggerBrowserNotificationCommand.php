<?php

namespace Drupal\private_message_nodejs\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to trigger browser notifications.
 */
class PrivateMessageNodejsTriggerBrowserNotificationCommand implements CommandInterface {

  /**
   * The user IDs of the members whose inboxes should be updated.
   *
   * @var array
   */
  protected $uids;

  /**
   * An array of data regarding the info to be sent.
   *
   * @var array
   */
  protected $message;

  /**
   * Constructs a PrivateMessageNodejsTriggerInboxUpdateCommand object.
   *
   * @param array $uids
   *   The user IDs of the members whose inboxes should be updated.
   * @param array $message
   *   An array of data regarding the info to be sent.
   *   Required Keys:
   *   - author (string): The name of the sender.
   *   - icon (string): The URL of the image to use in the popup.
   *   - title (string): The title to show in the popup.
   *   Optional:
   *   - link (string): A URL that clicking the private link will show.
   */
  public function __construct(array $uids, array $message) {
    $this->uids = $uids;
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'privateMessageNodejsNotifyBrowserOfNewMessage',
      'uids' => $this->uids,
      'message' => $this->message,
    ];
  }

}
