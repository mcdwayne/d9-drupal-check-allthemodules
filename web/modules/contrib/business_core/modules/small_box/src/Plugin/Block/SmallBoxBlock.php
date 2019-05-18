<?php

namespace Drupal\small_box\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a small box block.
 *
 * @Block(
 *   id = "small_box_block",
 *   admin_label = @Translation("Small box block")
 * )
 */
class SmallBoxBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'content' => '',
      'title' => '',
      'icon' => '',
      'link' => '',
      'col_classes' => 'col-lg-3 col-xs-6',
      'box_classes' => '',
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

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['title'],
    ];
    $form['icon'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Icon'),
      '#default_value' => $config['icon'],
    ];
    $form['link'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link'),
      '#default_value' => $config['link'],
    ];
    $form['col_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Col classes'),
      '#default_value' => $config['col_classes'],
    ];
    $form['box_classes'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Box classes'),
      '#default_value' => $config['box_classes'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['content'] = $form_state->getValue('content');
    $this->configuration['title'] = $form_state->getValue('title');
    $this->configuration['icon'] = $form_state->getValue('icon');
    $this->configuration['link'] = $form_state->getValue('link');
    $this->configuration['col_classes'] = $form_state->getValue('col_classes');
    $this->configuration['box_classes'] = $form_state->getValue('box_classes');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $conf = $this->configuration;
    $conf['content'] =\Drupal::token()->replace($conf['content']);
    return [
      '#theme' => 'small_box',
      '#configuration' => $conf,
    ];
  }

}
