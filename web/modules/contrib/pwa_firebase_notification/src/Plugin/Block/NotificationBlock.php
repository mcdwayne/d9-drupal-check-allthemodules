<?php

namespace Drupal\pwa_firebase_notification\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a button do enable/disable notifications.
 *
 * @Block(
 *   id = "pwa_firebase_notification_toggle",
 *   admin_label = @Translation("PWA Notification block"),
 *   category = @Translation("Notification"),
 * )
 */
class NotificationBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['view_mode' => 'button'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return [
      'view_mode' => [
        '#type' => 'select',
        '#title' => t('View mode'),
        '#default_value' => $this->configuration['view_mode'],
        '#options' => [
          'button' => t('Button'),
          'form' => t('Form'),
        ],
        '#required' => TRUE,
      ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['view_mode'] = $form_state->getValue('view_mode');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'pwa_firebase_notification_form_block',
      '#config' => $this->configuration,
    ];
  }

}
