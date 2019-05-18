<?php

namespace Drupal\html5history\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * An ajax command to send the browser history back one frame.
 */
abstract class AbstractStateCommand implements CommandInterface {

  /**
   * An array of context data to be added with the url.
   *
   * @var array|null
   */
  protected $state;

  /**
   * The title to associate with the history frame.
   *
   * @var string|null
   */
  protected $title;

  /**
   * The url to be put on the history stack.
   *
   * @var string
   */
  protected $url;

  /**
   * Creates a push history ajax command.
   *
   * @param array|null $state
   *   An array of context data to be added to the history stack or NULL.
   * @param string|null $title
   *   The title to be add to the history stack or NULL.
   * @param string $url
   *   The url to push onto the browser's history stack.
   */
  public function __construct($state, $title, $url) {
    $this->state = $state;
    $this->title = $state;
    $this->url = $url;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => $this->commandName(),
      'state' => $this->state,
      'title' => $this->title,
      'url' => $this->url,
    ];
  }

  /**
   * Gets the name of the ajax command to be executed.
   *
   * @return string
   *   The name of the ajax command to be executed.
   */
  abstract protected function commandName();

}
