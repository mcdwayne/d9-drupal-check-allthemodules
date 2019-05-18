<?php

namespace Drupal\encrypt_content_client\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;

/**
 * Provides a Block for updating ECC keys used for client-side encryption.
 *
 * @Block(
 *   id = "ecc_key_block",
 *   admin_label = @Translation("Client encryption"),
 *   category = @Translation("Forms"),
 * )
 */
class EccKeyBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#type' => 'markup',
      'form' => \Drupal::formBuilder()->getForm('\Drupal\encrypt_content_client\Form\UpdateKeysBlockForm'),
    ];
  }

}
