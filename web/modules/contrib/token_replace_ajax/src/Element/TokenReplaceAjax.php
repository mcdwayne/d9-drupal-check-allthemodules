<?php

/**
 * @file
 * Contains \Drupal\token_replace_ajax\Element\TokenReplaceAjax.
 */

namespace Drupal\token_replace_ajax\Element;

use Drupal\Core\Render\Element\FormElement;

/**
 * Token Replace AJAX form element.
 *
 * @FormElement("token_replace_ajax")
 */
class TokenReplaceAjax extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#attached' => [
        'library' => ['token_replace_ajax/token_replace_ajax'],
      ],
      '#input'    => TRUE,
      '#ajax'     => [
        'callback' => 'Drupal\token_replace_ajax\Controller\TokenReplaceAjaxController::ajax',
      ],
    ];
  }

}
