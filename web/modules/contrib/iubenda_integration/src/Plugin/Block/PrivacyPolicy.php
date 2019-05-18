<?php

namespace Drupal\iubenda_integration\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides the PrivacyPolicy block.
 *
 * @Block(
 *   id = "iubenda_integration_privacy_policy",
 *   admin_label = @Translation("Iubenda Integration: Privacy policy")
 * )
 */
class PrivacyPolicy extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'iubenda_integration_block' =>
        [
          'text_prefix' => '',
          'text' => 'Privacy Policy',
          'text_suffix' => '',
        ]
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $configurations_iubenda = $this->configuration['iubenda_integration_block'];

    $form['iubenda_integration_block'] = [
      '#type' => 'fieldset',
      '#title' => t('Iubenda Privacy settings'),
    ];
    $form['iubenda_integration_block']['text_prefix'] = [
      '#title' => t('Text prefix'),
      '#description' => t('Insert text of the Privacy Policy line have to
        precede Iubenda link'),
      '#type' => 'textarea',
      '#default_value' => $configurations_iubenda['text_prefix'],
    ];
    $form['iubenda_integration_block']['text'] = [
      '#title' => t('Link Text'),
      '#description' => t('Insert text that will be displayed as link'),
      '#type' => 'textfield',
      '#default_value' => $configurations_iubenda['text'],
      '#required' => TRUE,
    ];
    $form['iubenda_integration_block']['text_suffix'] = [
      '#title' => t('Text Suffix'),
      '#description' => t('Insert text of the Privacy Policy line have to follow
        Iubenda link'),
      '#type' => 'textarea',
      '#default_value' => $configurations_iubenda['text_suffix'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $configurations = $form_state->getValue('iubenda_integration_block');
    $this->configuration['iubenda_integration_block'] = $configurations;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
//    return [
//      '#type' => 'markup',
//      '#prefix' => $this->configuration['iubenda_integration_block']['text_prefix'],
//      '#suffix' => $this->configuration['iubenda_integration_block']['text_suffix'],
//      '#markup' => iubenda_integration_build_link(
//        $this->configuration['iubenda_integration_block']['text']),
//    ];
    return [
      '#theme' => 'block__iubenda_privacy_policy',
      '#pre_text' => $this->configuration['iubenda_integration_block']['text_prefix'],
      '#link' => iubenda_integration_build_link(
        $this->configuration['iubenda_integration_block']['text']),
      '#post_text' => $this->configuration['iubenda_integration_block']['text_suffix'],
    ];
  }

}
