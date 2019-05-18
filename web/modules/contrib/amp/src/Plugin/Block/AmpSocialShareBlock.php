<?php

namespace Drupal\amp\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\amp\AmpFormTrait;

/**
 * Provides an AMP Social Share block
 *
 * @Block(
 *   id = "amp_social_share_block",
 *   admin_label = @Translation("AMP Social Share block"),
 * )
 */
class AmpSocialShareBlock extends BlockBase {

  use AmpFormTrait;

  /**
   * AMP libraries
   *
   * Expected by AmpFormTrait.
   *
   * @return array
   *   The names of the AMP libraries used by this block.
   */
  private function getLibraries() {
    return ['amp/amp.social-share'];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();
    $build = [
      'amp_social_share' => [
        '#theme' => 'amp_social_share',
        '#providers' => $config['providers'],
        '#app_id' => $config['app_id'],
        '#attached' => ['library' => $this->getLibraries()],
      ]
    ];
    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    // Retrieve existing configuration for this block.
    $config = $this->getConfiguration();

    $options = [
      'facebook' => $this->t('Facebook'),
      'twitter' => $this->t('Twitter'),
      'linkedin' => $this->t('LinkedIn'),
      'pinterest' => $this->t('Pinterest'),
      'gplus' => $this->t('G+'),
      'whatsapp' => $this->t('WhatsApp'),
      'tumblr' => $this->t('Tumblr'),
      'email' => $this->t('Email'),
    ];
    $form['providers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Providers'),
      '#default_value' => isset($config['providers']) ? $config['providers'] : '',
      '#options' => $options,
      '#description' => $this->t('Select the providers you want to allow users to share to.')
    ];
    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Facebook provider id'),
      '#description' => $this->t('Required if Facebook is one of the selected items.'),
      '#default_value' => isset($config['app_id']) ? $config['app_id'] : '',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->setConfigurationValue('providers', array_filter($form_state->getValue('providers')));
    $this->setConfigurationValue('app_id', $form_state->getValue('app_id'));
  }
}
