<?php

namespace Drupal\js_callback_examples\Plugin\Js;

use Drupal\js\Plugin\Js\JsCallbackBase;
use Drupal\user\UserInterface;

/**
 * @JsCallback(
 *   id = "js_callback_examples.email",
 *   parameters = {
 *     "user" = {
 *       "type" = "entity:user"
 *     },
 *   },
 * )
 */
class Email extends JsCallbackBase {

  /**
   * {@inheritdoc}
   */
  public function validate(UserInterface $user = NULL) {
    if (!$user || !$user->isActive()) {
      drupal_set_message(t('You must enter a valid user.'), 'error');
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(UserInterface $user = NULL) {
    return [
      '#markup' => $user->getEmail(),
      '#attached' => [
        'drupalSettings' => [
          'testing' => 'test',
        ],
      ],
    ];
  }

}
