<?php

namespace Drupal\appbanners\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Component\Utility\Html;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Provides route responses for the App Banners module.
 */
class AppbannersController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The simple config object of the module.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('appbanners.settings');
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
   * Page callback for manifest.json.
   */
  public function manifestPage() {

    $config = $this->config;

    $manifest = [
      'prefer_related_applications' => TRUE,
      'related_applications' => [
        [
          'platform' => 'play',
          'id' => Html::escape($config->get('android_app_id')),
        ],
      ],
    ];

    $name = $config->get('android_name');
    if (!empty($name)) {
      $manifest['name'] = Html::escape($name);
    }

    $short_name = $config->get('android_short_name');
    if (!empty($short_name)) {
      $manifest['short_name'] = Html::escape($short_name);
    }

    $icon = $config->get('android_icon');
    if (!empty($icon)) {
      $manifest['icons'][] = [
        'src' => Html::escape($icon),
        'type' => 'image/png',
        'sizes' => '192x192',
      ];
    }

    $icon_large = $config->get('android_icon_large');
    if (!empty($icon_large)) {
      $manifest['icons'][] = [
        'src' => Html::escape($icon_large),
        'type' => 'image/png',
        'sizes' => '512x512',
      ];
    }

    return new JsonResponse($manifest);

  }

}
