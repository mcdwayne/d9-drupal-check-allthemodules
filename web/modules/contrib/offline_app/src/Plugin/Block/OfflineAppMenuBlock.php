<?php

/**
 * @file
 * Contains \Drupal\offline_app\Plugin\Block\OfflineAppMenuBlock.
 */

namespace Drupal\offline_app\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'OfflineAppMenuBlock' block.
 *
 * @Block(
 *  id = "offline_app_menu_block",
 *  admin_label = @Translation("Offline menu"),
 * )
 */
class OfflineAppMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
  /**
   * Stores the configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, CurrentPathStack $current_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('path.current')
    );
  }



  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $menu = $this->configFactory->get('offline_app.appcache')->get('menu');
    if (!empty($menu)) {

      $current_path = $this->currentPath->getPath();

      $i = 0;
      $menu_links = [];
      $menu_configuration = explode("\n", trim($menu));
      foreach ($menu_configuration as $menu_link) {
        $is_active = FALSE;

        list($path, $title) = explode('/', trim($menu_link));
        if (empty($path) && empty($title)) {
          continue;
        }

        $route = 'offline_app.appcache.offline';
        $menu_links['link_' . $i] = [
          'title' => $title,
          'url' => Url::fromRoute($route, ['offline_alias' => $path]),
        ];

        $rendered_link = Url::fromRoute($route, ['offline_alias' => $path])->toString();
        if ($rendered_link == $current_path) {
          $is_active = TRUE;
        }

        if ($is_active) {
          $menu_links['link_' . $i]['attributes'] = ['class' => ['is-active']];
        }

        $i++;
      }

      $build['#theme'] = 'links';
      $build['#links'] = $menu_links;
      $build['#cache']['contexts'] = ['user.permissions', 'url'];
      $build['#cache']['tags'][] = 'appcache';
      $build['#cache']['tags'][] = $current_path;
    }

    return $build;
  }

}
