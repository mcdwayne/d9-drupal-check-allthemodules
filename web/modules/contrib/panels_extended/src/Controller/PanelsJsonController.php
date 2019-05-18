<?php

namespace Drupal\panels_extended\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Controller\EntityViewController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Defines a generic controller to render a single entity as JSON.
 *
 * Not using a CacheableJsonResponse as we don't want to cache the JSONoutput.
 * If, in the future we want to do this, we need to make sure that we add the
 * correct cache dependencies for the responses.
 */
class PanelsJsonController extends EntityViewController {

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $_entity, $view_mode = 'full') {
    $page = $this->entityManager
      ->getViewBuilder($_entity->getEntityTypeId())
      ->view($_entity, $view_mode);

    $response = new JsonResponse($page);

    return $response;
  }

}
