<?php

namespace Drupal\custom_configurations\Routing;

use Drupal\Component\Utility\Html;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\custom_configurations\CustomConfigurationsManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class CustomConfigurationsRoutes implements ContainerInjectionInterface {

  /**
   * Drupal\custom_configurations\CustomConfigurationsManager definition.
   *
   * @var \Drupal\custom_configurations\CustomConfigurationsManager
   */
  protected $customConfigurationsManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Creates a ProductMenuLink instance.
   *
   * @param \Drupal\custom_configurations\CustomConfigurationsManager $custom_configurations_manager
   *   Custom configurations service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language manager service.
   */
  public function __construct(CustomConfigurationsManager $custom_configurations_manager, LanguageManagerInterface $language_manager) {
    $this->customConfigurationsManager = $custom_configurations_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('custom_configurations.manager'),
      $container->get('language_manager')
    );
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   An array of route objects.
   */
  public function routes() {
    $routes = [];
    $plugins = $this->customConfigurationsManager->getConfigPlugins();

    foreach ($plugins as $plugin) {

      $cat_url = '';
      $url_id = Html::getId($plugin['id']);

      if (!empty($plugin['category_id'])) {
        $routes['custom_configurations.' . $plugin['category_id'] . '.category'] = new Route(
          '/admin/structure/custom-configurations/' . $plugin['category_id'],
          [
            '_controller' => '\Drupal\custom_configurations\Controller\CustomConfigurationsController::getIndex',
            '_title' => $plugin['category'],
          ],
          ['_permission' => 'access custom configurations'],
          ['_admin_route' => TRUE]
        );
        $cat_url = $plugin['category_id'] . '/';
      }

      $routes['custom_configurations.' . $plugin['id'] . '.form'] = new Route(
        '/admin/structure/custom-configurations/' . $cat_url . $url_id . '/{plugin_id}/{language}',
        [
          '_form' => '\Drupal\custom_configurations\Form\CustomConfigurationsForm',
          '_title_callback' => '\Drupal\custom_configurations\Form\CustomConfigurationsForm::titleCallback',
          'plugin_id' => NULL,
          'language' => NULL,
        ],
        ['_permission' => 'access custom configurations'],
        ['_admin_route' => TRUE]
      );

      if ($this->customConfigurationsManager->languagesAvailable()) {
        $languages = $this->languageManager->getLanguages();

        foreach ($languages as $language_code => $lang) {
          $task_id = $plugin['id'] . '.' . $language_code;
          $url_id = Html::getId($plugin['id'] . '-' . $language_code);

          $routes['custom_configurations.' . $task_id . '.form'] = new Route(
            '/admin/structure/custom-configurations/' . $cat_url . $url_id . '/{plugin_id}/{language}',
            [
              '_form' => '\Drupal\custom_configurations\Form\CustomConfigurationsForm',
              '_title_callback' => '\Drupal\custom_configurations\Form\CustomConfigurationsForm::titleCallback',
              'plugin_id' => NULL,
              'language' => NULL,
            ],
            ['_permission' => 'access custom configurations'],
            ['_admin_route' => TRUE]
          );
        }
      }

    }
    return $routes;
  }

}
