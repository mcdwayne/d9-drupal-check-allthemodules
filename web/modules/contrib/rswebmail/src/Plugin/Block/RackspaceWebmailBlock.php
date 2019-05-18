<?php
/**
 * @file
 * Contains \Drupal\rswebmail\Plugin\Block\RackspaceWebmailBlock.
 */
namespace Drupal\rswebmail\Plugin\Block;
use Drupal\Core\Block\BlockBase;
/**
 * Provides Rackspace webmail block.
 *
 * @Block(
 *   id = "rackspace_webmail_block",
 *   admin_label = @Translation("Rackspace webmail new message count"),
 *   category = @Translation("Blocks")
 * )
 */
class RackspaceWebmailBlock extends BlockBase {
  
  /**
   * {@inheritdoc}
   */
  public function build() {
    return rswebmail_block_content();
  }
}