<?php

/**
 * @file
 * Contains \Drupal\brew_tools\Plugin\Block\PlatoGravBlock.
 */

namespace Drupal\brew_tools\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Convert between Plato and Gravity' block.
 * 
 * @Block(
 *  id = "plato_grav_block",
 *  admin_label = @Translation("Convert between Plato and Gravity Block")
 * )
 */
class PlatoGravBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return $form;
  }

  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\brew_tools\Form\BrewToolsCalcPlatoGravForm');
    return drupal_render($form);
  }

}
