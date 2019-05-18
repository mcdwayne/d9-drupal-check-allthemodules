<?php

namespace Drupal\entity_toolbar\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command that signals that toolbar content has loaded.
 *
 * This command instructs the client to initialize Drupal behaviors
 * on the loaded entity toolbar.
 *
 * This command is implemented by
 * Drupal.AjaxCommands.prototype.EntityToolbarLoadedCommand() defined in
 * js/entity.toolbar.js.
 */
class EntityToolbarLoadedCommand implements CommandInterface {

  /**
   * Selector for the Entity Toolbar tab to affect.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs an EntityToolbarLoadedCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   */
  public function __construct($selector) {
    $this->selector = $selector;
  }

  /**
   * Implements Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'EntityToolbarLoadedCommand',
      'tab' => $this->selector,
    ];
  }

}
