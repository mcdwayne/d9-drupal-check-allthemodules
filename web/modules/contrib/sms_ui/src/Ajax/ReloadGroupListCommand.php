<?php

/**
 * @file
 * Contains \Drupal\sms_ui\Ajax\ReloadGroupListCommand.
 */

namespace Drupal\sms_ui\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for reloading a group list.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.smsUiReloadGroupList.
 */
class ReloadGroupListCommand implements CommandInterface {

  /**
   * The DOM element selector of the group list to reload.
   *
   * @var string
   */
  protected $selector;

  /**
   * The values to be loaded into the DOM element.
   *
   * @var array
   */
  protected $values;

  /**
   * Constructs a \Drupal\sms_ui\Ajax\ReloadGroupListCommand object.
   *
   * @param string $selector
   *   The selector for the DOM element.
   * @param array $values
   *   The values to be loaded into the DOM element.
   */
  public function __construct($selector, array $values) {
    $this->selector = $selector;
    $this->values = $values;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return array(
      'command' => 'smsUiReloadGroupList',
      'selector' => $this->selector,
      'values' => $this->values,
    );
  }

}
