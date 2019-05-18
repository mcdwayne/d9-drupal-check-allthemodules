<?php

/**
 * @file
 * Contains \Drupal\edit_ui\Ajax\MessageCommand.
 */

namespace Drupal\edit_ui\Ajax;

use Drupal\Core\Ajax\CommandInterface;
use Drupal\Core\Ajax\CommandWithAttachedAssetsTrait;

/**
 * Provides an AJAX command for showing the messages.
 */
class MessageCommand implements CommandInterface {

  use CommandWithAttachedAssetsTrait;

  /**
   * Default animation speed.
   *
   * @var int
   */
  const DEFAULT_SPEED = 500;

  /**
   * The renderable array for messages.
   *
   * @var array
   */
  protected $content = array('#type' => 'status_messages');

  /**
   * The animation speed.
   *
   * @var int
   */
  protected $speed;

  /**
   * Constructs a MessageCommand object.
   */
  public function __construct($speed = self::DEFAULT_SPEED) {
    $this->speed = $speed;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return array(
      'command' => 'editUiAddMessage',
      'content' => $this->getRenderedContent(),
      'speed' => $this->speed,
    );
  }

}
