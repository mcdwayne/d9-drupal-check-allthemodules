<?php

namespace Drupal\commerce_affirm\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides an Affirm banner image block.
 *
 * @Block(
 *   id = "commerce_affirm_banner_block",
 *   admin_label = @Translation("Affirm Banner"),
 * )
 */
class Banner extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'banner_size' => '468x60',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['banner_size'] = [
      '#type' => 'radios',
      '#title' => $this->t('Banner size'),
      '#description' => $this->t('Select the image size of the banner you want to display in this block.'),
      '#default_value' => $this->configuration['banner_size'],
    ];

    foreach ($this->bannerSizes() as $size) {
      $element = [
        '#theme' => 'commerce_affirm_banner_image',
        '#banner_size' => $size,
        '#width' => 120,
      ];
      $form['banner_size']['#options'][$size] = $size . ' ' . \Drupal::service('renderer')->render($element);
    }
    return $form;
  }

  /**
   * Return the possible banner sizes.
   */
  protected function bannerSizes() {
    return [
      '120x90',
      '150x100',
      '170x100',
      '190x100',
      '234x60',
      '300x50',
      '468x60',
      '300x250',
      '336x280',
      '540x200',
      '728x90',
      '800x66',
      '250x250',
      '280x280',
      '120x240',
      '120x600',
      '234x400',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['banner_size'] = $form_state->getValue('banner_size');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'commerce_affirm_banner_image',
      '#banner_size' => $this->configuration['banner_size'],
    ];
  }

}
