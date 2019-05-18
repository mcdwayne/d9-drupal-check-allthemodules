<?php

namespace Drupal\hotjar;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HotjarSettings.
 *
 * @package Drupal\hotjar
 */
class HotjarSettings implements HotjarSettingsInterface, ContainerInjectionInterface {

  const HOTJAR_PAGES = "/admin\n/admin/*\n/batch\n/node/add*\n/node/*/*\n/user/*/*";

  /**
   * Hotjar config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Settings.
   *
   * @var array
   */
  protected $settings;

  /**
   * HotjarSettings constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory.
   */
  public function __construct(
    ConfigFactoryInterface $configFactory
  ) {
    $this->config = $configFactory->get('hotjar.settings');
    $this->getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    if (!$this->settings) {
      $settings = (array) $this->config->getOriginal();
      $settings += [
        'account' => NULL,
        'snippet_version' => 6,
        'visibility_pages' => 0,
        'pages' => static::HOTJAR_PAGES,
        'visibility_roles' => 0,
        'roles' => [],
      ];

      if (empty($settings['snippet_version'])) {
        $settings['snippet_version'] = 6;
      }
      $this->settings = $settings;
    }
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default = NULL) {
    $this->getSettings();
    return array_key_exists($key, $this->settings) ? $this->settings[$key] : $default;
  }

}
