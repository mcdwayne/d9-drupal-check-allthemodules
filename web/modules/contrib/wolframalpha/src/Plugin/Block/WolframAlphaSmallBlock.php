<?php
namespace Drupal\wolframalpha\Plugin\Block;
use Drupal\block\BlockBase;
use Drupal\Core\Session\AccountInterface;
/**
 * Provides a 'WolframAlphaSmall' block.
 *
 * @Block(
 *   id = "wolframAlphaSmall_block",
 *   admin_label = @Translation("Wolfram Alpha Small block"),
 * )
 */
class WolframAlphaSmallBlock extends BlockBase {
  /**
   * Implements \Drupal\block\BlockBase::blockBuild().
   */
  public function build() {
    return array(
      '#children' => '<script id="WolframAlphaScript" src="http://www.wolframalpha.com/input/embed/?type=small" type="text/javascript"></script>',
    );
  }
  /**
   * Implements \Drupal\block\BlockBase::access().
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('access content');
  }}
?>