<?php

namespace Drupal\integro\Plugin\Integro\Operation;

use Drupal\integro\OperationInterface;

/**
 * @IntegroOperation(
 *   id = "native",
 *   label = "Native",
 * )
 */
class NativeOperation extends OperationBase implements OperationInterface {

  /**
   * {@inheritdoc}
   */
  public function execute() {
    // To be implemented in descendants.
    return [];
  }

}
