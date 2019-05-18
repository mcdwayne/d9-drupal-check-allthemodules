<?php

namespace Drupal\fancy_login\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Defines the refresh page ajax command.
 */
class FancyLoginRefreshPageCommand implements CommandInterface {

  /**
   * A boolean indicating whether or not the popup should be closed first.
   *
   * @var bool
   */
  protected $closePopup;

  /**
   * Constructs a FancyLoginRefreshPageCommand object.
   *
   * @param bool $closePopup
   *   A boolean indicating whether or not the popup should be closed before
   *   refreshing.
   */
  public function __construct($closePopup) {
    $this->closePopup = $closePopup;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'fancyLoginRefreshPage',
      'closePopup' => $this->closePopup,
    ];
  }

}
