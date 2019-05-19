<?php

namespace Drupal\tint\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TintEmbedBlock' block.
 *
 * @Block(
 *  id = "tint_embed_block",
 *  admin_label = @Translation("TINT Embed HTML"),
 *  category = @Translation("TINT"),
 *  deriver = "Drupal\tint\Plugin\Derivative\TintEmbedBlockDerivative"
 * )
 */
class TintEmbedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['tint_html'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add TINT HTML'),
      '#default_value' => !empty($this->configuration['tint_html']) ? $this->configuration['tint_html'] : '',
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $tint_html = $form_state->getValue('tint_html');
    $this->configuration['tint_html'] = $tint_html;
    $this->configuration['strip_tint_html'] = strip_tags($tint_html, '<div>');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    return [
      '#theme' => 'tint',
      '#strip_tint_html' => $config['strip_tint_html'],
    ];
  }

}
