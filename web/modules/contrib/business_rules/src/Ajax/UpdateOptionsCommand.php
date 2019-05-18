<?php

namespace Drupal\business_rules\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to update form options.
 *
 * @package Drupal\business_rules\Ajax
 */
class UpdateOptionsCommand implements CommandInterface {

  protected $elementId;

  protected $options;

  /**
   * UpdateOptionsCommand constructor.
   *
   * @param string $elementId
   *   The element html id.
   * @param array $options
   *   The element options [key, value].
   */
  public function __construct($elementId, array $options) {
    $this->elementId = $elementId;
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'updateOptionsCommand',
      'method' => 'html',
      'elementId' => $this->elementId,
      'options' => $this->options,
    ];
  }

}
