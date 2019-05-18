<?php

namespace Drupal\drm\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Ajax command to update form options.
 *
 * @package Drupal\drm\Ajax
 */
class UpdateDrmOptionsCommand implements CommandInterface {

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
      'command' => 'update_drm_options',
      'method' => 'html',
      'elementId' => $this->elementId,
      'options' => $this->options,
    ];
  }

}
