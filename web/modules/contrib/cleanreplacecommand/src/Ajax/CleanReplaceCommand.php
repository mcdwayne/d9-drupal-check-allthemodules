<?php

namespace Drupal\cleanreplacecommand\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class CleanReplaceCommand.
 */
class CleanReplaceCommand implements CommandInterface {

  private $selector;

  private $element;

  public function __construct($selector, $element) {

    $this->selector = $selector;
    $this->element = $element;
  }

  /**
   * Render custom ajax command.
   *
   * @return array|\Drupal\cleanreplacecommand\Ajax\ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'cleanReplace',
      'selector' => $this->selector,
      'element' => $this->element,
    ];
  }

}
