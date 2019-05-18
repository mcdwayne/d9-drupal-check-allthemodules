<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Plugin\Block\StrikeTempBlock.
 */

namespace Drupal\brew_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Boil Time' block.
 * 
 * @Block(
 *  id = "boil_time_block",
 *  admin_label = @Translation("Boil Time Block")
 * )
 */
class BoilTimeBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        return $form;
    }

    public function build() {
        $form = \Drupal::formBuilder()->getForm('Drupal\brew_tools\Form\BrewToolsBoilTimeForm');
        return drupal_render($form);
    }

}
