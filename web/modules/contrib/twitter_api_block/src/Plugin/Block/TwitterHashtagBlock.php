<?php

namespace Drupal\twitter_api_block\Plugin\Block;

use Drupal\Core\Cache\UncacheableDependencyTrait;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a 'TwitterHashtagBlock' block.
 *
 * @Block(
 *   id = "twitter_hashtag_block",
 *   admin_label = @Translation("Twitter - Hashtag block"),
 *   category = @Translation("Content")
 * )
 */
class TwitterHashtagBlock extends TwitterBlockBase {

  use UncacheableDependencyTrait;

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $form['options']['warning'] = [
      '#type'   => 'item',
      '#markup' => $this->t("This block is deprecated. Please use the 'Twitter - Search block' instead."),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [];
  }

}
