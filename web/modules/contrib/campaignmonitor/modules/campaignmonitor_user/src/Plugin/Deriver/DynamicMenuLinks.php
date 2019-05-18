<?php

namespace Drupal\campaignmonitor_user\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a deriver for user entered paths of menu links.
 *
 * The assumption is that the number of manually entered menu links are lower
 * compared to entity referenced ones.
 */
class DynamicMenuLinks extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Constructs a MenuLinkContentDeriver instance.
   *
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The query factory.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menu_link_manager
   *   The menu link manager.
   */
  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $config = \Drupal::config('campaignmonitor_user.settings');
    $links = array(
      'route_name' => 'campaignmonitor.user.subscriptions',
      'id' => 'campaignmonitor_user.subscriptions',
    );
    $links['title'] =  $config->get('subscription_heading');
    $links['description'] = 'Subscribe to Campaign Monitor';
    $links['parent'] = 'main:';
    $links['enabled'] = 1;

    return $links;
  }
}
