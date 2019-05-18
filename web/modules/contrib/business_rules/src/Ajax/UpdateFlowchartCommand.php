<?php

namespace Drupal\business_rules\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to update flowchart.
 *
 * @package Drupal\business_rules\Ajax
 */
class UpdateFlowchartCommand implements CommandInterface {

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'updateFlowchart',
    ];
  }

}
