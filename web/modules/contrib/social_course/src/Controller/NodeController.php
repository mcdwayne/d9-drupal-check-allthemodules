<?php

namespace Drupal\social_course\Controller;

use Drupal\node\Controller\NodeController as NodeControllerBase;

/**
 * Class NodeController.
 */
class NodeController extends NodeControllerBase {

  /**
   * {@inheritdoc}
   */
  public function addPage() {
    /** @var \Drupal\social_course\Access\ContentAccessCheck $access_checker */
    $access_checker = \Drupal::service('social_course.access_checker');
    $account = \Drupal::currentUser();
    $build = [
      '#theme' => 'node_add_list',
      '#cache' => [
        'tags' => $this->entityTypeManager()->getDefinition('node_type')->getListCacheTags(),
      ],
    ];

    $content = [];

    // Only use node types the user has access to.
    foreach ($this->entityTypeManager()->getStorage('node_type')->loadMultiple() as $type) {
      $access = $this
        ->entityTypeManager()
        ->getAccessControlHandler('node')
        ->createAccess($type->id(), NULL, [], TRUE)
        ->andIf($access_checker->access($account, $type));

      if ($access->isAllowed()) {
        $content[$type->id()] = $type;
      }

      $this->renderer->addCacheableDependency($build, $access);
    }

    // Bypass the node/add listing if only one content type is available.
    if (count($content) == 1) {
      $type = array_shift($content);
      return $this->redirect('node.add', [
        'node_type' => $type->id(),
      ]);
    }

    $build['#content'] = $content;

    return $build;
  }

}
