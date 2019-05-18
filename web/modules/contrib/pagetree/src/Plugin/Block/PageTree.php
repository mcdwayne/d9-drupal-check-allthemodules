<?php
namespace Drupal\pagetree\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an page tree block.
 *
 * The block needs to be added to the themes header region to take effect.
 *
 * @Block(
 *   id = "pagetree_block",
 *   admin_label = @Translation("Page Tree Block"),
 * )
 */
class PageTree extends BlockBase
{

    /**
     * Builds the page tree block.
     *
     * @return array
     */
    public function build()
    {
        $build = array();
        $augment = false;

        // If user is logged in and has permission to edit page nodes, enable edit mode.
        $user = \Drupal::currentUser();
        if ($user->hasPermission('use pagetree') && !\Drupal::service('router.admin_context')->isAdminRoute()) {
            // Attach library for editing
            $build['#attached']['library'] = array('pagetree/page-tree');

            // Set values for displaying page tree.
            $build['#attached']['drupalSettings']['pagetree']['currentNode'] = \Drupal::routeMatch()->getRawParameter('node');
            $build['#attached']['drupalSettings']['pagetree']['defaultLanguage'] = \Drupal::languageManager()->getDefaultLanguage()->getId();
            $build['#theme'] = 'pagetree';
            $build['#menus'] = \Drupal::service('pagetree.tree')->get();
            $build['#defaultlanguage'] = \Drupal::languageManager()->getDefaultLanguage()->getId();
        }
        // Prevent block from being cached.
        $build['#cache'] = [
            'max-age' => 0,
        ];

        return $build;
    }
}
