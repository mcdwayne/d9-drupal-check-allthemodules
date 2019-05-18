<?php

namespace Drupal\indeed_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a Jobs Search Form Block.
 *
 *@Block(
 *   id = "Indeed_SearchBlock",
 *   admin_label = @Translation("Job Search Form")
 * )
 *
 *
 */
class Indeed_SearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form_class = '\Drupal\indeed_search\Form\JobsSearchForm';
    $block['form'] = \Drupal::formBuilder()->getForm($form_class);
    $formof = \Drupal::service('renderer')->render($block['form']);
    return [
      '#type' => 'markup',
      '#markup' => $formof,
    ];
  }

}
