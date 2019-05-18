<?php

/**
 * @file
 * Contains \Drupal\bmi\Plugin\Block\BodyMassIndexBlock.
 */

namespace Drupal\bmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'BodyMassIndexBlock' block.
 *
 * @Block(
 *  id = "body_mass_index_block",
 *  admin_label = @Translation("Body mass index block"),
 * )
 */
class BodyMassIndexBlock extends BlockBase {


  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\bmi\Form\BmiForm');
  }

}
