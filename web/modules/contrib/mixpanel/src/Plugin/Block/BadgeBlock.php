<?php

/**
 * @file
 * Contains \Drupal\mixpanel\Plugin\Block\BadgeBlock.
 */

namespace Drupal\mixpanel\Plugin\Block;

use Drupal\block\BlockBase;

/**
 * Provides a 'Mixpanel badge' block.
 *
 * @Block(
 *   id = "mixpanel_badge",
 *   admin_label = @Translation("Mixpanel badge")
 * )
 */
class BadgeBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function settings() {
    return array(
      'cache' => DRUPAL_CACHE_GLOBAL,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array('badge_type' => 'dark');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, &$form_state) {
    $config = $this->getConfiguration();
    $form['badge_type'] = array(
      '#type' => 'select',
      '#title' => t('Badge type'),
      '#options' => array(
        'dark' => t('Dark'),
        'light' => t('Light'),
      ),
      '#default_value' => $config['badge_type'],
      '#description' => t('Mixpanel provides both a light and dark version of its badge.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, &$form_state) {
    $this->setConfigurationValue('badge_type', $form_state['values']['badge_type']);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $img = 'badge_' . ($config['badge_type'] == 'dark' ? 'blue' : 'light') . '.png';
    return array(
      '#markup' => '<a href="http://mixpanel.com/f/partner"><img src="http://mixpanel.com/site_media/images/partner/' . $img . '" alt="Real Time Web Analytics" /></a>'
    );
  }

}
