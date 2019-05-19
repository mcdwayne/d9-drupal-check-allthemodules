<?php

/**
 * @file
 * Contains \Drupal\subsite\Plugin\Block\SubsiteSocialLinksBlock.
 */

namespace Drupal\subsite\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides social links block using links from the subsite social links field.
 *
 * @Block(
 *   id = "subsite_social_links",
 *   admin_label = @Translation("Subsite social links"),
 *   category = @Translation("Subsite")
 * )
 */
class SubsiteSocialLinksBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
        if ($subsite_node->hasField('field_subsite_social_links')) {
          // Turn this into a list of links.
          /** @var FieldItemList $field */
          $field_item_list = $subsite_node->get('field_subsite_social_links');
          /** @var FieldCollection $item */
          $social_links = array();
          foreach ($field_item_list as $item) {
            $social_links[] = $item->getFieldCollectionItem()->get('field_subsite_social_links_link')->get(0)->view();
          }
//          $social_links['#theme_wrappers'] = array('item_list');
          return array(
            '#theme' => 'item_list',
            '#items' => $social_links,
          );
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
