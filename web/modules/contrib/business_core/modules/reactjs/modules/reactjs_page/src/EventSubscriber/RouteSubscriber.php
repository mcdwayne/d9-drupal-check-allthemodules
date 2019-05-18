<?php

namespace Drupal\reactjs_page\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Created by PhpStorm.
 * User: u14
 * Date: 17-6-1
 * Time: 下午3:20
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  public function routes() {
    $collection = new RouteCollection();

    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      $entity_type_id = $entity_type->id();
      $route = new Route(
        '/' . $entity_type_id,
        [
          '_controller' => 'Drupal\reactjs_page\Controller\ReactjsPageController::entityList',
          '_title' => $entity_type->getLabel()->getUntranslatedString(),
          'entity_type_id' => $entity_type_id,
        ],
        [
        '_access' => 'TRUE', // TODO
          ]
      );
      $collection->add('reactjs_page.' . $entity_type_id, $route);
    }

    $pages = $this->entityTypeManager->getStorage('page')
      ->loadMultiple();
    /** @var \Drupal\reactjs\PageInterface $page */
    foreach ($pages as $page) {
      $path = $page->getThirdPartySetting('reactjs_page', 'path');
      if (!empty($path)) {
        $route = new Route(
          $path,
          [
            '_controller' => 'Drupal\reactjs_page\Controller\ReactjsPageController::handle',
            '_title' => $page->label(),
            'page_id' => $page->id(),
          ],
          [
            '_access' => 'TRUE', // TODO
          ]
        );
        $collection->add('reactjs_page.' . $page->id(), $route);
      }
    }

    return $collection;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // TODO: Implement alterRoutes() method.
  }

}
