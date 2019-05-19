<?php

namespace Drupal\synhelper\Controller;

/**
 * @file
 * Contains \Drupal\app\Controller\AjaxResult.
 */

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;

/**
 * Controller routines for page example routes.
 */
class AjaxResult extends ControllerBase {

  /**
   * AJAX Responce.
   */
  public static function ajax($wrapper, $otvet, $commands = FALSE) {
    $output = '';
    if ($otvet || $commands) {
      $output .= '<pre>';
      $output .= $otvet;
      if ($commands) {
        $output .= implode("\n", $commands);
      }
      $output .= '</pre>';
    }
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand("#" . $wrapper, $output));
    return $response;
  }

  /**
   * AJAX Button.
   */
  public static function button($function, $button = "Отправить", $color = 'primary') {
    return [
      '#type' => 'submit',
      '#value' => $button,
      '#attributes' => ['class' => ['btn', 'btn-xs', 'btn-' . $color]],
      '#ajax'   => [
        'callback' => $function,
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => ""],
      ],
    ];
  }

  /**
   * AJAX Button.
   */
  public static function select($function, $options, $default) {
    $default_value = FALSE;
    if (isset($options[$default])) {
      $default_value = $default;
    }
    else {
      $default_value = 'select';
      $options['select'] = 'Select';
    }
    return [
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $default_value,
      '#ajax'   => [
        'callback' => $function,
        'effect'   => 'fade',
        'progress' => ['type' => 'throbber', 'message' => ""],
      ],
    ];
  }

}
