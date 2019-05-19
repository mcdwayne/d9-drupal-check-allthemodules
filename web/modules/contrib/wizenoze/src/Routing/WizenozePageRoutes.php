<?php

namespace Drupal\wizenoze\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a route subscriber to register a url for serving search pages.
 */
class WizenozePageRoutes implements ContainerInjectionInterface {

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new WizenozeRoutes object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager) {
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity.manager'), $container->get('language_manager'));
  }

  /**
   * Returns an array of route objects.
   *
   * @return \Symfony\Component\Routing\Route[]
   *   TODO: need to configure based on entity
   */
  public function routes() {
    $routes = [];
    $is_multilingual = $this->languageManager->isMultilingual();

    /* @var $wizenoze_page \Drupal\wizenoze\WizenozePageInterface */
    foreach ($this->entityManager->getStorage('wizenoze')->loadMultiple() as $wizenoze_page) {

      // Default path.
      $default_path = $wizenoze_page->getPath();

      // Loop over all languages so we can get the translated path (if any).
      foreach ($this->languageManager->getLanguages() as $language) {

        // Check if we are multilingual or not.
        if ($is_multilingual) {
          $path = $this->languageManager->getLanguageConfigOverride($language->getId(), 'wizenoze.wizenoze.' . $wizenoze_page->id())
            ->get('path');

          if (empty($path)) {
            $path = $default_path;
          }
        }
        else {
          $path = $default_path;
        }

        $args = [
          '_controller' => 'Drupal\wizenoze\Controller\WizenozePageController::page',
          '_title_callback' => 'Drupal\wizenoze\Controller\WizenozePageController::title',
          'wizenoze_page_name' => $wizenoze_page->id(),
        ];

        // Use clean urls or not.
        if ($wizenoze_page->getCleanUrl()) {
          $path .= '/{keys}';
          $args['keys'] = '';
        }

        $routes['wizenoze_page.' . $language->getId() . '.' . $wizenoze_page->id()] = new Route(
            $path, $args, [
              '_permission' => 'view wizenoze',
            ]
        );
      }
    }

    return $routes;
  }

}
