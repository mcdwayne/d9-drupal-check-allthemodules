<?php

namespace Drupal\ad_choices_link\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * MenuLinksDiscoveredAlter service.
 */
class LinkInsert {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * AdChoices Link Config.
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
    $this->config = $this->configFactory->get('ad_choices_link.settings');
  }

  /**
   * Adds the AdChoices link to the configured menu.
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
    $links[$menu . '.ad_choices'] = [
      'title' => 'AdChoices',
      'description' => 'AdChoices Link',
      'route_name' => 'ad_choices_link.nojs',
      'menu_name' => $menu,
      'provider' => 'ad_choices_link',
      'options' => [
        'attributes' => [
          'id' => '_bapw-link',
        ],
      ],
    ];
  }

}
