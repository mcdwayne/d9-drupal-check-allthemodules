<?php

namespace Drupal\webform_cart\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class DataLayerPush.
 */
class DataLayerPush implements CommandInterface {


  protected $data;

  /**
   * DataLayerPush constructor.
   *
   * @param $data
   */
  public function __construct($data) {
      $this->data = $data;
  }

  /**
   * Render custom ajax command.
   *
   * @return ajax
   *   Command function.
   */
  public function render() {
    return [
      'command' => 'DataLayerPush',
      'message' => "Webform Cart dataLayer Push Event : $this->data",
      'data' => $this->data,
    ];
  }

}
