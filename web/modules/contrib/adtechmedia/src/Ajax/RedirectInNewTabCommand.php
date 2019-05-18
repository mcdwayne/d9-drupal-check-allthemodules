<?php

namespace Drupal\atm\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class RedirectInNewTabCommand.
 */
class RedirectInNewTabCommand implements CommandInterface {

  /**
   * The URL that will be loaded into window.location.
   *
   * @var string
   */
  protected $url;

  /**
   * Constructs an RedirectCommand object.
   *
   * @param string $url
   *   The URL that will be loaded into window.location. This should be a full
   *   URL.
   */
  public function __construct($url) {
    $this->url = $url;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return [
      'command' => 'redirectInNewTab',
      'url' => $this->url,
    ];
  }

}
