<?php

namespace Drupal\contextual_views\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\Block\ViewsBlock;

/**
 * Provides a 'ContentListing' block.
 *
 * @Block(
 *  id = "views_block:listing_content-contextual_views_block_1",
 *  admin_label = @Translation("Content: Teaser (contextual)"),
 * )
 */
class ContentListing extends ViewsBlock {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'type' => $this->t('Content type'),
      'status' => $this->t('Published'),
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#default_value' => $this->configuration['type'],
      '#options' => node_type_get_names(),
      '#weight' => '0',
    ];
    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#empty_value' => 'all',
      '#empty_option' => $this->t('All'),
      '#options' => [
        '1' => $this->t('Published'),
        '0' => $this->t('Unpublished'),
      ],
      '#default_value' => $this->configuration['status'],
      '#weight' => '0',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $this->configuration['type'] = $form_state->getValue('type');
    $this->configuration['status'] = $form_state->getValue('status');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $args = [
      $this->configuration['type'],
      $this->configuration['status'],
    ];
    $this->view->setArguments($args);

    return parent::build();
  }

}
