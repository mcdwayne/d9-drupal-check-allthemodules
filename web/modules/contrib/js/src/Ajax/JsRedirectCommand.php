<?php

namespace Drupal\js\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines an AJAX command to set the window.location, loading that URL.
 *
 * @ingroup ajax
 */
class JsRedirectCommand implements CommandInterface {

  /**
   * Flag indicating whether redirection should be forced in the browser.
   *
   * @var bool
   */
  protected $force;

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
   *   The URL that will be loaded in the browser. This should be a full URL.
   * @param bool $force
   *   Flag indicating whether redirection should be forced in the browser.
   */
  public function __construct($url, $force = FALSE) {
    $this->url = $url;
    $this->force = $force;
  }

  /**
   * Implements \Drupal\Core\Ajax\CommandInterface:render().
   */
  public function render() {
    return array(
      'command' => 'redirect',
      'force' => $this->force,
      'url' => $this->url,
    );
  }

}
