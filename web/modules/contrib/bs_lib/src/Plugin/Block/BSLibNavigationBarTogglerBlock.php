<?php

namespace Drupal\bs_lib\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'BSLibNavigationBarTogglerBlock' block.
 *
 * @Block(
 *  id = "bs_lib_navigation_bar_toggler_block",
 *  admin_label = @Translation("Navigation bar toggler"),
 * )
 */
class BSLibNavigationBarTogglerBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'toggle_label' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['toggle_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#description' => $this->t('Label for toggle button. Leave it empty to not use it.'),
      '#maxlength' => 255,
      '#default_value' => $config['toggle_label'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['toggle_label'] = $form_state->getValue('toggle_label');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    // Drupal still does not support button element so we need to provide our
    // own theme implementation for now.
    // @todo Consider refactoring this to button element when
    // https://www.drupal.org/node/1671190 is done.
    return [
      // Anything that you pass in #options will be merged into variables in
      // template_preprocess_bs_lib_toggle_button().
      '#options' => [
        'label' => $config['toggle_label'],
        'attributes' => [
          'class' => ['navbar-toggler'],
          // Our bs_bootstrap implementation is offering close element for
          // off-canvas navigation so we can put min space between screen edge
          // and off-canvas menu to minimum.
          'data-minspace' => 0,
          'data-toggle' => 'collapse',
          'data-target' => '#navbarResponsive',
          'aria-controls' => 'navbarResponsive',
          'aria-expanded' => 'false',
          'aria-label' => $this->t('Toggle navigation'),
        ],
      ],
      '#theme' => 'bs_lib_toggle_button',
      '#attached' => ['library' => ['bs_lib/navigation']],
    ];
  }

}
