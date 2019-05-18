<?php

namespace Drupal\gdrp_compliance\Controller;

/**
 * @file
 * Contains \Drupal\gdrp_compliance\Controller\FormWarning.
 */
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller routines for page example routes.
 */
class FormWarning extends ControllerBase {

  /**
   * Add "Cookie & Privacy Policy" warning.
   */
  public static function addWarning(&$form) {
    $href = \Drupal::config('gdrp_compliance.settings')->get('from-morelink');
    $form['gdrp-warning'] = [
      '#type' => 'checkbox',
      '#title' => t("I have read and agree to the Cookie & Privacy Policy"),
      '#default_value' => FALSE,
      '#required' => TRUE,
      '#attributes' => [
        'required' => 'required',
      ],
      '#description' => t(
        "<a href='@href' target='_blank'>Cookie & Privacy Policy for Website</a>",
        ['@href' => $href]
      ),
      '#weight' => 99,
    ];
  }

}
