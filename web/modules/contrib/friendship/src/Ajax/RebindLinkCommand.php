<?php

namespace Drupal\friendship\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class RebindLinkCommand.
 *
 * @package Drupal\friendship\Ajax
 */
class RebindLinkCommand implements CommandInterface {

  /**
   * Selector.
   *
   * @var string
   */
  protected $selector;

  /**
   * Link.
   *
   * @var string
   */
  protected $link;

  /**
   * Title.
   *
   * @var string
   */
  protected $title;

  /**
   * RebindLinkCommand constructor.
   *
   * @param string $selector
   *   Element class.
   * @param string $link
   *   Link URL.
   * @param string $title
   *   Link title.
   */
  public function __construct($selector, $link, $title) {
    $this->selector = $selector;
    $this->link = $link;
    $this->title = $title;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'rebindLink',
      'selector' => $this->selector,
      'link' => $this->link,
      'title' => $this->title,
    ];
  }

}
