<?php
namespace Drupal\social_stats\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;

/**
 * Provides a 'Social Stats' block for the current node.
 *
 * @Block(
 *  id = "social_stats_block",
 *  admin_label = @Translation("Social Stats of node"),
 *  category = @Translation("Social Stats"),
 *  context = {
 *    "node" = @ContextDefinition("entity:node", required = TRUE)
 *   }
 * )
 */
class SocialStatsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->hasPermission('access social stats block')) {
      return AccessResult::allowed();
    }
    else {
      return AccessResult::forbidden();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $block = array();

    /** @var $node_ctx \Drupal\Core\Plugin\Context\Context */
    $node_ctx = $this->getContext('node');

    /** @var $node Drupal\node\Entity\Node */
    $node = $node_ctx->getContextData()->getValue();
    if ($node) {
      $connection = Database::getConnection();
      $result = $connection->select('social_stats_total', 's')
        ->fields('s', array('nid', 'total'))
        ->condition('nid', $node->id(), '=')
        ->execute()->fetchAll();
      if ($result) {
        $total = $result[0]->total;
        $block = array(
          '#markup' => \Drupal::translation()->formatPlural($total, '1 Share', '@count Shares'),
        );
      }
      else {
        $block = array(
          '#markup' => t('0 Share'),
        );
      }
    }
    return $block;
  }

}
