<?php

namespace Drupal\ad_entity\Plugin\Block;

use Drupal\Core\Form\FormStateInterface;

/**
 * Defines iFrame blocks for displaying Advertisement.
 *
 * @Block(
 *   id = "ad_display_iframe",
 *   admin_label = @Translation("iFrame: Display for Advertisement"),
 *   category = @Translation("iFrame: Display for Advertisement"),
 *   deriver = "Drupal\ad_entity\Plugin\Derivative\AdDisplayBlock"
 * )
 */
class AdDisplayIframeBlock extends AdDisplayBlock {

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);
    $settings = $this->getConfiguration();

    $form['iframe']['#weight'] = 20;
    $form['iframe']['width'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('iFrame width'),
      '#size' => 10,
      '#field_prefix' => 'width="',
      '#field_suffix' => '"',
      '#default_value' => isset($settings['iframe']['width']) ? $settings['iframe']['width'] : '1000',
      '#weight' => 10,
    ];
    $form['iframe']['height'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('iFrame height'),
      '#size' => 10,
      '#field_prefix' => 'height="',
      '#field_suffix' => '"',
      '#default_value' => isset($settings['iframe']['height']) ? $settings['iframe']['height'] : '250',
      '#weight' => 20,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $settings = $this->getConfiguration();
    $id = $this->getDerivativeId();
    $build = [];
    if ($ad_display = $this->adDisplayStorage->load($id)) {
      if ($ad_display->access('view')) {
        $build[$id] = [
          '#theme' => 'ad_display_iframe',
          '#ad_display' => $ad_display,
          '#width' => $settings['iframe']['width'],
          '#height' => $settings['iframe']['height'],
        ];
      }
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['iframe']['width'] = '1000';
    $config['iframe']['height'] = '250';
    return $config;
  }

}
