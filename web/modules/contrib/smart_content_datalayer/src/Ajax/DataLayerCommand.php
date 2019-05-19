<?php

namespace Drupal\smart_content_datalayer\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Class DataLayerCommand
 *
 * @package Drupal\smart_content_datalayer\Ajax
 */
class DataLayerCommand implements CommandInterface {

  /**
   * An array containing data about the winning variation.
   *
   * @var array
   */
  protected $data;

  /**
   * DataLayerCommand constructor.
   *
   * @param array $data
   */
  public function __construct(array $data) {
    $this->data = $data;
  }

  /**
   * {@inheritDoc}
   */
  public function render() {
    return [
      'command' => 'dataLayerCommand',
      'data' => $this->data,
    ];
  }

}
