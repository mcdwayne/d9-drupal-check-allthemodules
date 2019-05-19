<?php

/**
 * @file
 * Contains \Drupal\subsite\Plugin\Block\SubsiteFooterLinksBlock.
 */

namespace Drupal\subsite\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a footer menu block using links from the subsite node links field.
 *
 * @Block(
 *   id = "subsite_footer_links",
 *   admin_label = @Translation("Subsite footer links"),
 *   category = @Translation("Subsite")
 * )
 */
class SubsiteFooterLinksBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $current_bid = 0;

    if ($node = \Drupal::requestStack()->getCurrentRequest()->get('node')) {
      $current_bid = empty($node->book['bid']) ? 0 : $node->book['bid'];
    }

    if ($current_bid) {
      /** @var \Drupal\node\Entity\Node $theme_node */
      $subsite_node = \Drupal\node\Entity\Node::load($current_bid);

      if ($subsite_node->getType() == 'sub_site') {
        // Expect theme ref field.
        if ($subsite_node->hasField('field_subsite_footer_links')) {
          // Now get the value of the subsite footer links field.
          if ($footer_links = $subsite_node->get('field_subsite_footer_links')->view(array('label' => 'hidden'))) {
            $build = $footer_links;
            return $build;
          }
        }

        // Mimic main menu.
//      $build['#theme'] = 'menu__main';
//      $build['#items'] = $items;
      }
    }

    return array();
  }

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }
}
