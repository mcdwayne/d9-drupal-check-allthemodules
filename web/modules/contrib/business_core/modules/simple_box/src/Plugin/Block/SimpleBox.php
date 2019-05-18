<?php

namespace Drupal\simple_box\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a simple box block.
 *
 * @Block(
 *   id = "simple_box_block",
 *   admin_label = @Translation("Simple box block")
 * )
 */
class SimpleBox extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->configuration;

    $token_tree = [
      '#theme' => 'token_tree_link',
      '#token_types' => [],
    ];
    $rendered_token_tree = \Drupal::service('renderer')->render($token_tree);
    $form['content'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Content'),
      '#description' => $this->t('This field supports tokens. @browse_tokens_link', ['@browse_tokens_link' => $rendered_token_tree]),
      '#default_value' => $config['content'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['content'] = $form_state->getValue('content');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => \Drupal::token()->replace($this->configuration['content']),
    ];
  }

}
