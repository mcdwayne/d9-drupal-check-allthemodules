<?php

namespace Drupal\commerce_affirm\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides an Affirm site modal.
 *
 * @Block(
 *   id = "commerce_affirm_site_modal_block",
 *   admin_label = @Translation("Site Modal"),
 * )
 */
class SiteModal extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'page_type' => "product",
      'link_text' => $this->t('Learn more'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['page_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Site modal page type'),
      '#description' => $this->t('This identifies your promotional messaging so Affirm can apply the necessary customizations based on the page they’re displayed.'),
      '#options' => _commerce_affirm_page_types(),
      '#default_value' => $this->configuration['page_type'],
    ];
    $form['link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The site modal link text'),
      '#default_value' => $this->configuration['link_text'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['page_type'] = $form_state->getValue('page_type');
    $this->configuration['link_text'] = $form_state->getValue('link_text');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'commerce_affirm_site_modal',
      '#page_type' => $this->configuration['page_type'],
      '#link_text' => $this->configuration['link_text'],
    ];
  }

}
