<?php

/**
 * @file
 * Contains \Drupal\content_callback_block\Plugin\Block\ContentCallbackBlock.
 */

namespace Drupal\content_callback_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'Content callback' block.
 *
 * @Block(
 *   id = "content_callback_block",
 *   admin_label = @Translation("Content callback block"),
 *   deriver = "Drupal\content_callback_block\Plugin\Derivative\ContentCallbackBlock"
 * )
 */
class ContentCallbackBlock extends BlockBase {

  /**
   * The Content Callback object
   *
   * @var \Drupal\content_callback\Plugin\ContentCallbackInterface
   */
  protected $content_callback;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $derivative = $this->getDerivativeId();
    $manager = \Drupal::service('plugin.manager.content_callback');
    $this->content_callback = $manager->createInstance($derivative);
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    return $this->content_callback->access($account);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    return $this->content_callback->render(array());
  }

}
