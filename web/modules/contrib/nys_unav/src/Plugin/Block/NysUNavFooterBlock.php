<?php
/**
 * @file
 * Contains \Drupal\nys_unav\Plugin\Block\NysUNavFooterBlock.php
 */

namespace Drupal\nys_unav\Plugin\Block;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a NYS uNav footer block
 *
 * @Block(
 *     id = "nys_unav_footer_block",
 *     admin_label = @Translation("NYS uNav Footer"),
 *     category = @Translation("NYS")
 * )
 */

class NysUNavFooterBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    protected function blockAccess(AccountInterface $account) {
        return $account->hasPermission('administer nys unav');
    }

    /**
     * {@inheritdoc}
     */
    public function build() {
        // return equivalent to theme function
        $config = \Drupal::config('nys_unav.settings');
        switch ($config->get('nys_unav.nys_unav_interactive')) {
            case '0':
                $block = array(
                    '#theme' => 'nys_unav_footer_static',
                );
                break;
            case '1':
                $block = array(
                    '#theme' => 'nys_unav_footer_interactive',
                );
                break;
        }
        return $block;
    }
}