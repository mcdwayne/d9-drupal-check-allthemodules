<?php

namespace Drupal\fancy_login\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines the redirect command.
 */
class FancyLoginRedirectCommand implements CommandInterface {

  /**
   * A boolean indicating whether or not the popup should be closed first.
   *
   * @var bool
   */
  protected $closePopup;

  /**
   * The redirect destination.
   *
   * @var string
   */
  protected $destination;

  /**
   * Constructs a FancyLoginRedirectCommand object.
   *
   * @param bool $closePopup
   *   A boolean indicating whether or not the popup should be closed before
   *   redirecting.
   * @param string $destination
   *   The redirect destination.
   */
  public function __construct($closePopup, $destination) {
    $this->closePopup = $closePopup;
    $this->destination = $destination;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'fancyLoginRedirect',
      'closePopup' => $this->closePopup,
      'destination' => $this->destination,
    ];
  }

}
