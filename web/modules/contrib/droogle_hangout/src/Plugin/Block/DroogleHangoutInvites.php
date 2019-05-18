<?php

/**
 * @file
 * Contains \Drupal\droogle_hangout\Plugin\Block\DroogleHangoutInvites.
 */

namespace Drupal\droogle_hangout\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'Droogle Hangout' block.
 *
 * @Block(
 *   id = "droogle_hangout_invites",
 *   admin_label = @Translation("Droogle Hangout Invites Popup")
 * )
 */
class DroogleHangoutInvites extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Get public file path.
    if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('public://')) {
      $file_path = $wrapper->realpath();
    }
    else {
      $file_path = '';
    }

    $hangout_list = array(
      '#theme' => 'droogle_hangout_invitee_window',
    );
    return $hangout_list;
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // The 'Droogle Hangout' block is permanently cacheable, because its
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
