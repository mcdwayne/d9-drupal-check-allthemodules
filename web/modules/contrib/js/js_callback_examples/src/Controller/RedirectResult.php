<?php

namespace Drupal\js_callback_examples\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;

/**
 * Default controller for the js_callback_examples module.
 */
class RedirectResult extends ControllerBase {

  /**
   * The content for this controller.
   *
   * @return mixed
   *   Render array.
   */
  public function content() {
    return [
      '#markup' => t('You clicked a link where the destination was <a href=":redirect" target="_blank">:redirect</a>, but was redirect to <a href=":redirect_result" target="_blank">:redirect_result</a>.', [
        ':redirect' => Url::fromRoute('js_callback_examples.redirect')->toString(),
        ':redirect_result' => Url::fromRoute('js_callback_examples.redirect_result')->toString(),
      ])
    ];
  }

}
