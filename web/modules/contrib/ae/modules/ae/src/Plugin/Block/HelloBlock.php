<?php

namespace Drupal\ae\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Hello' Block.
 *
 * @Block(
 *   id = "hello_block",
 *   admin_label = @Translation("Sign Up"),
 *   category = @Translation("Hello World"),
 * )
 */
class HelloBlock extends BlockBase {

    public function __construct()
    {
        $this->state = \Drupal::state();
    }

    public function build() {

        return [
            '#theme' => 'signup',
            '#socials' => $this->getSelectedSocials(),
            '#fields' => $this->state->get('fields'),
            '#general_settings' => $this->state->get('general_settings'),
            '#basic_options' => $this->state->get('basic_options'),
            '#email_options' => $this->state->get('email_options'),
            '#text_options' => $this->state->get('text_options'),
            '#performance_options' => $this->state->get('performance_options'),
            '#attached' => [
                'library' => [
                    'ae/script',
                ],
            ],
        ];

    }

    public function getSelectedSocials() {
        $socials = [];

        $social_networks = $this->state->get('socials')['socials'];
        foreach($social_networks as $social=>$text) {
            if($text != 0 || $text != "0")
                $socials[] = $text;
        }

        return $socials;
    }

}
?>