<?php

namespace Drupal\bcubed_adfuscate\Plugin\bcubed\Action;

use Drupal\bcubed\ActionBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Show message to users with adblockers requiring the adblocker be disabled.
 *
 * @Action(
 *   id = "adfuscate",
 *   label = @Translation("AdFuscate"),
 *   description = @Translation("Show message to users with adblockers requiring the adblocker be disabled"),
 *   settings = {
 *     "message" = "",
 *     "mask_style" = "width: 100%; height: 100%; position: fixed; z-index: 100000; top: 0; left: 0; opacity: 0; background: radial-gradient(rgba(231,231,231,0.9),rgba(120,120,120,0.9)); transition: opacity ease 0.3s; overflow: scroll;",
 *     "mask_style_active" = "opacity: 1;",
 *     "message_style" = "font-size: 24px; max-width: 750px; margin: 300px auto;"
 *   }
 * )
 */
class AdFuscate extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function getLibrary() {
    return 'bcubed_adfuscate/adfuscate';
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form['message'] = [
      '#type' => 'textarea',
      '#title' => 'Message',
      '#description' => 'Message to display in content overlay',
      '#default_value' => $this->settings['message'],
      '#required' => TRUE,
    ];

    $form['mask_style'] = [
      '#type' => 'textarea',
      '#title' => 'Mask Style',
      '#description' => 'CSS to style background mask with',
      '#default_value' => $this->settings['mask_style'],
    ];

    $form['mask_style_active'] = [
      '#type' => 'textarea',
      '#title' => 'Mask Style (Active)',
      '#description' => 'CSS to style background mask with when it is active. Can be used for transitions.',
      '#default_value' => $this->settings['mask_style_active'],
    ];

    $form['message_style'] = [
      '#type' => 'textarea',
      '#title' => 'Message Container Style',
      '#description' => 'CSS to style the message container. Inline styles in the message content are supported for further customization.',
      '#default_value' => $this->settings['message_style'],
    ];

    return $form;
  }

}
