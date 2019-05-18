<?php

namespace Drupal\ofed_switcher\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class OfedSwitcherMenuLinkDerivative extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    return [
      [
        'title' => 'Go to front',
        'menu_name' => 'admin',
        'parent' => 'ofed_switcher.tools',
        'weight' => -100,
        'route_name' => 'ofed_switcher.switch_to_frontend',
      ] + $base_plugin_definition,
      [
        'title' => 'Go to CMS',
        'menu_name' => 'admin',
        'parent' => 'ofed_switcher.tools',
        'weight' => -100,
        'route_name' => 'ofed_switcher.switch_to_backend',
      ] + $base_plugin_definition,
    ];
  }

}