<?php

namespace Drupal\private_message_nodejs\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class to insert older private messages into a private message thread.
 */
class PrivateMessageNodejsTriggerNewMessagesCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'privateMessageNodejsTriggerNewMessages',
    ];
  }

}
