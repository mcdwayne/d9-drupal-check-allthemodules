<?php

namespace Drupal\reactjs_page\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\frontend\PageInterface;

/**
 * Returns responses for Reactjs page routes.
 */
class ReactjsPageController extends ControllerBase {

  public function handle($page_id) {
    $page = $this->entityTypeManager()->getStorage('page')
      ->load($page_id);
    return $this->handlePage($page);
  }

  public function handlePage(PageInterface $page) {
    $build = [
      '#markup' => '<div id="reactjs-page" page="' . $page->uuid() . '"></div>',
      '#attached' => ['library' => ['reactjs/page']],
    ];

    return $build;
  }

  public function entityList($entity_type_id) {
    $definition = $this->entityTypeManager()->getDefinition($entity_type_id);
    $page_storage = $this->entityTypeManager()->getStorage('page');
    $page = $page_storage->load($entity_type_id . '-list');
    if (!$page) {
      $page = $page_storage->create([
        'id' => $entity_type_id . '-list',
        'label' => $definition->getLabel(),
        'components' => [
          [
            'type' => 'EntityList',
            'class' => ['col-xs-12'],
            'settings' => [
              'entity_type' => $entity_type_id,
            ],
          ],
        ]
      ]);
      $page->save();
    }
    return $this->handlePage($page);
  }

}
