<?php

namespace Drupal\ghostery\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * MenuLinksDiscoveredAlter service.
 */
class AdChoicesLinkInsert {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Ghostery Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a menulinksdiscoveredalter object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('ghostery.settings');
  }

  /**
   * Adds the ghostery link to the configured menu.
   *
   * @param array $links
   *   The array of discovered links to modify.
   */
  public function insert(array &$links) {
    $menu = $this->config->get('menu');

    if (empty($menu)) {
      return;
    }

    // @todo come up with a better fall back link.
    $links[$menu . '.ghostery'] = [
      'title' => 'AdChoices',
      'description' => 'AdChoices Ghostery Link',
      'route_name' => 'ghostery.nojs',
      'menu_name' => $menu,
      'provider' => 'ghostery',
      'options' => [
        'attributes' => [
          'id' => '_bapw-link',
        ],
      ],
    ];
  }

}
