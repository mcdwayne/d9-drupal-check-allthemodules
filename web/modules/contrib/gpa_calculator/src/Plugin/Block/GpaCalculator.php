<?php

/**
 * @file
 * Contains \Drupal\gpa_calculator\Plugin\Block\GpaCalculator.
 */

namespace Drupal\gpa_calculator\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'GPA Calculator' block.
 *
 * @Block(
 *   id = "gpa_calculator",
 *   admin_label = @Translation("GPA Calculator")
 * )
 */
class GpaCalculator extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return \Drupal::formBuilder()->getForm('Drupal\gpa_calculator\Form\GpaCalculatorForm');
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // The 'GPA Calculator' block is permanently cacheable, because its
    // contents can never change.
    $form['cache']['#disabled'] = TRUE;
    $form['cache']['max_age']['#value'] = Cache::PERMANENT;
    $form['cache']['#description'] = t('This block is always cached forever, it is not configurable.');

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return Cache::PERMANENT;
  }

  /**
   * {@inheritdoc}
   */
  public function isCacheable() {
    return TRUE;
  }

}
