<?php

namespace Drupal\ghostery\Hooks;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * GhosteryPreprocessMenu service.
 */
class AdChoicesLinkAlter {

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
   * Constructs a ghosterypreprocessmenu object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('ghostery.settings');
  }

  /**
   * Add library and image to ghostery link.
   *
   * @param array $variables
   *   The render array.
   */
  public function alter(array &$variables) {
    $menu = $this->config->get('menu');
    if ($variables['menu_name'] != $menu) {
      return;
    }

    if (isset($variables['items'][$menu . '.ghostery'])) {
      $variables['#attached']['library'][] = 'ghostery/ghostery';
      $variables['#attached']['drupalSettings']['ghostery'] = [
        'pid' => $this->config->get('pid'),
        'ocid' => $this->config->get('ocid'),
        'jsPath' => drupal_get_path('module', 'ghostery') . '/js',
      ];
      $title = '<img id="_bapw-icon" alt="Ad Choices Icon" /><span>AdChoices</span>';
      $variables['items'][$menu . '.ghostery']['title'] = new FormattableMarkup($title, []);
    }
  }

}
