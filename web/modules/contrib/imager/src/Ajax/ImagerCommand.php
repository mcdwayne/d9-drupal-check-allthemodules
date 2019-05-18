<?php

namespace Drupal\imager\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class ImagerCommand.
 *
 * @package Drupal\imager\Ajax
 */
class ImagerCommand implements CommandInterface {

  /**
   * ImagerCommand constructor.
   *
   * @param array $data
   *   Data array.
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * Create the command array.
   *
   * @return array
   *   Render array for command.
   */
  public function render() {
    return array(
      'command' => 'ImagerCommand',
      'data' => $this->data,
    );
  }

}
