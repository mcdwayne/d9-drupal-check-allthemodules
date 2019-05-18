<?php

namespace Drupal\private_message_nodejs\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to return autocomplete member results to the browser.
 */
class PrivateMessageNodejsTriggerInboxUpdateCommand implements CommandInterface {

  /**
   * The user IDs of the members whose inboxes should be updated.
   *
   * @var array
   */
  protected $uids;

  /**
   * Constructs a PrivateMessageNodejsTriggerInboxUpdateCommand object.
   *
   * @param array $uids
   *   The user IDs of the members whose inboxes should be updated.
   */
  public function __construct(array $uids) {
    $this->uids = $uids;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'privateMessageNodejsTriggerInboxUpdateCommand',
      'uids' => $this->uids,
    ];
  }

}
