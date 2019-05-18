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
 *   id = "settings_block",
 *   admin_label = @Translation("Settings"),
 *   category = @Translation("Settings"),
 * )
 */

class SettingsBlock extends BlockBase{

    public function __construct()
    {
        $this->state = \Drupal::state();
    }

    public function build() {

        return [
            '#theme' => 'setting',
            '#heading' => 'Change Password',
            '#attached' => [
                'library' => [
                    'ae/script',
                ],
            ],
        ];

    }
}