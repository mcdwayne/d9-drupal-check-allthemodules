<?php

namespace Drupal\ooyala\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for invoking an arbitrary jQuery method.
 *
 * The 'invoke' command will instruct the client to invoke the given jQuery
 * method with the supplied arguments on the elements matched by the given
 * selector. Intended for simple jQuery commands, such as attr(), addClass(),
 * removeClass(), toggleClass(), etc.
 *
 * This command is implemented by Drupal.AjaxCommands.prototype.invoke()
 * defined in misc/ajax.js.
 *
 * @ingroup ajax
 */
class OoyalaPlayerResponse implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * If the command is a response to a request from an #ajax form element then
   * this value can be NULL.
   *
   * @var string
   */
  protected $selector;

  /**
   * List of players returned from the API.
   *
   * @var array
   */
  protected $players;

  /**
   * Constructs an InvokeCommand object.
   *
   * @param string $selector
   *   A jQuery selector.
   * @param array $players
   *   An array of players to pass to the method.
   */
  public function __construct($selector, $players) {
    $this->selector = $selector;
    $this->players = $players;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'ooyalaReplacePlayers',
      'selector' => $this->selector,
      'players' => $this->players,
    ];
  }

}
