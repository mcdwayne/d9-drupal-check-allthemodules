<?php

/**
 * @file
 * Contains \Drupal\igrowl\Ajax\GrowlCommand.
 */

namespace Drupal\igrowl\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for calling iGrowl from the jQuery iGrowl plugin.
 *
 * @ingroup ajax
 */
class GrowlCommand implements CommandInterface {
  /**
   * The options that control the display/message of an iGrowl.
   * @var array
   */
  protected $options;

  /**
   * Constructs a GrowlCommand object.
   * At a minimum, the $options array should contain a message, type, title, and icon.
   * They will be defaulted if not provided, but are the four commonly set options.
   * @param array $options
   */
  public function __construct($options = []) {
    $this->options = array_merge(self::defaultOptions(), $options);
  }

  /**
   * Return an array of default options for iGrowl.
   * @return array
   */
  public static function defaultOptions() {
    return [
      'type' => 'info',
      'title' => NULL,
      'message' => NULL,
      'icon' => NULL,
      'small' => FALSE,
      'delay' => 2500,
      'spacing' => 30,
      'placement' => array(
        'x' => 'right',
        'y' => 'top'
      ),
      'offset' => array(
        'x' => 20,
        'y' => 20
      ),
      'animation' => TRUE,
      'animShow' => 'bounceIn',
      'animHide' => 'bounceOut',
      'onShow' => NULL,
      'onShown' => NULL,
      'onHide' => NULL,
      'onHidden' => NULL,
    ];
  }

  /**
   * Returns the options set for this GrowlCommand object.
   * @return string
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'igrowl',
      'settings' => array('options' => $this->options),
    ];
  }
}