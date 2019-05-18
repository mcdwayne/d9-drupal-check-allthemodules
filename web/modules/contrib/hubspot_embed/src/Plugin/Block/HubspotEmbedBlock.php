<?php

namespace Drupal\hubspot_embed\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Display Hubspot Form.
 *
 * @Block(
 *   id = "hubspot_embed",
 *   admin_label = @Translation("Hubspot Embed")
 * )
 */
class HubspotEmbedBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'embed' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['embed'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hubspot Embed code'),
      '#description' => $this->t('Paste the embed code from hubspot here.'),
      '#default_value' => $this->configuration['embed'],
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $embed = $form_state->getValue('embed');
    $this->configuration['embed'] = $embed;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'hubspot_embed',
      '#embed' => $this->configuration['embed'],
    ];
  }

}
