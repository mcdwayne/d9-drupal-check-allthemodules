<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Plugin\Block\StrikeTempBlock.
 */

namespace Drupal\brew_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Calc ABV Block' block.
 * 
 * @Block(
 *  id = "calc_abv_block",
 *  admin_label = @Translation("Calc ABV Block")
 * )
 */
class CalcAbvBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function blockForm($form, FormStateInterface $form_state) {
        return $form;
    }

    public function build() {
        $form = \Drupal::formBuilder()->getForm('Drupal\brew_tools\Form\BrewToolsCalcAbvForm');
        return drupal_render($form);
    }

}
