<?php

/**
 * @file
 * Contains \Drupal\sweetalert\Ajax\SweetAlertCommand.
 */

namespace Drupal\sweetalert\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * AJAX command for calling SweetAlert from the jQuery SweetAlert plugin.
 *
 * @ingroup ajax
 */
class SweetAlertCommand implements CommandInterface {
  /**
   * The options that control the display/message of an SweetAlert.
   * @var array
   */
  protected $options;

  /**
   * Constructs a SweetAlertCommand object.
   * At a minimum, the $options array should contain title, text, and type.
   * They will be defaulted if not provided, but they are the four commonly set options.
   * @param array $options
   */
  public function __construct($options = array()) {
    $this->options = array_merge(self::defaultOptions(), $options);
  }

  /**
   * Return an array of default options for SweetAlert.
   * @return array
   */
  public static function defaultOptions() {
    return [
      'title' => '',
      'text' => '',
      'type' => null,
      'allowOutsideClick' => false,
      'showConfirmButton' => true,
      'showCancelButton' => false,
      'closeOnConfirm' => true,
      'closeOnCancel' => true,
      'confirmButtonText' => 'OK',
      'confirmButtonColor' => '#8CD4F5',
      'cancelButtonText' => 'Cancel',
      'imageUrl' => null,
      'imageSize' => null,
      'timer' => null,
      'customClass' => '',
      'html' => false,
      'animation' => true,
      'allowEscapeKey' => true,
      'inputType' => 'text',
      'inputPlaceholder' => '',
      'inputValue' => '',
      'showLoaderOnConfirm' => false
    ];
  }

  /**
   * Returns the options set for this SweetAlertCommand object.
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
      'command' => 'sweetalert',
      'settings' => array('options' => $this->options),
    ];
  }
}