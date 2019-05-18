<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Plugin\Block\StrikeTempBlock.
 */

namespace Drupal\brew_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Strike Temperature' block.
 * 
 * @Block(
 *  id = "strike_temp_block",
 *  admin_label = @Translation("Strike Temperature Block")
 * )
 */
class StrikeTempBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        return $form;
    }

    public function build() {
        $form = \Drupal::formBuilder()->getForm('Drupal\brew_tools\Form\BrewToolsStrikeTempForm');
        return drupal_render($form);
    }

}
