<?php
namespace Drupal\chat_channels\Ajax;

use Drupal\Core\Ajax\CommandInterface;

class RefreshMessageCommand implements CommandInterface {
  /**
   * @var int $channelId
   */
  protected $channelId;

  /**
   * RefreshMessageCommand constructor.
   *
   * @param $channelId
   */
  public function __construct($channelId) {
    $this->channelId = $channelId;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   *
   * @return array
   */
  public function render() {
    return [
      'command' => 'chatChannelRefreshMessage',
      'channelId' => $this->channelId,
      'scrollToLastMessage' => true,
    ];
  }
}