<?php

namespace Drupal\hangouts\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\hangouts\HangoutsUtils;

/**
 * Provides a hangouts block.
 *
 * @Block(
 *   id = "hangouts_block",
 *   admin_label = @Translation("Hangouts block"),
 * )
 */
class HangoutsBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    return [
      '#theme' => 'hangouts',
      '#hangouts_gid' => $config['hangouts_gid'],
      '#hangouts_size' => $config['hangouts_size'],
      '#attached' => [
        'library' => [
          'hangouts/hangouts-twig',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $config = $this->getConfiguration();

    $form['form_hangouts_gid'] = [
      '#type' => 'email',
      '#title' => $this->t('Google ID'),
      '#description' => $this->t('Enter your Google ID (e-mail) here'),
      '#default_value' => isset($config['hangouts_gid']) ? $config['hangouts_gid'] : NULL,
      '#required' => TRUE,
    ];

    $form['form_hangouts_radios'] = [
      '#type' => 'radios',
      '#title' => $this->t('Button size'),
      '#description' => $this->t('Choose button size you like'),
      '#options' => HangoutsUtils::getHangoutsImages(),
      '#default_value' => isset($config['hangouts_size']) ? $config['hangouts_size'] : NULL,
      '#required' => TRUE,
      '#attributes' => [
        'required' => TRUE,
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['hangouts_gid'] = $values['form_hangouts_gid'];
    $this->configuration['hangouts_size'] = $values['form_hangouts_radios'];
    return parent::blockSubmit($form, $form_state);
  }

}
