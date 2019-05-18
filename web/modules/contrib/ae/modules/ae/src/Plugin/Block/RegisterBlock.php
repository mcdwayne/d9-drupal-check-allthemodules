<?php
/**
 * Created by PhpStorm.
 * User: aksha
 * Date: 2017-11-10
 * Time: 1:32 PM
 */

namespace Drupal\ae\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Settings' Block.
 *
 * @Block(
 *   id = "register_block",
 *   admin_label = @Translation("Register Block"),
 *   category = @Translation("Register"),
 * )
 */

class RegisterBlock extends BlockBase{

    public function __construct()
    {
        $this->state = \Drupal::state();
    }

    public function build() {

//        return [
//            '#markup' => $this->t('First time user? Sign Up <a href="#"  data-ae-register-window="true">here</a><br> Existing User? <a href="#" data-ae-login-window="true">Log In</a>'),
//        ];

        return [
            '#theme' => 'register',
            '#attached' => [
                'library' => [
                    'ae/script',
                ],
            ],
        ];

    }
}