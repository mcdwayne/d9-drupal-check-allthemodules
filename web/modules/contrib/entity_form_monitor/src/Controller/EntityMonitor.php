<?php

namespace Drupal\entity_form_monitor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Defines a controller to find the latest update values of entities.
 */
class EntityMonitor extends ControllerBase {

  /**
   * Returns the entity updates.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JsonResponse object.
   */
  public function getUpdates(Request $request) {
    $entity_ids = $request->request->get('entity_ids');

    if (empty($entity_ids) || !is_array($entity_ids)) {
      throw new AccessDeniedHttpException();
    }

    $updates = [];
    foreach ($entity_ids as $entity_id) {
      // The entity ID is a combination of entity-type:entity-id.
      // @see _entity_form_monitor_process_entity_form()
      list($entity_type, $id) = explode(':', $entity_id);

      // An invalid entity type or ID should deny access.
      if (!$this->entityTypeManager()->hasDefinition($entity_type) || !is_numeric($id)) {
        throw new AccessDeniedHttpException();
      }

      // @todo Should we be using loadUnchanged() here to ensure we have the absolute latest data?
      if ($entity = $this->entityTypeManager()->getStorage($entity_type)->load($id)) {
        // Check that the user has access to update the entity.
        if (!($entity instanceof ContentEntityInterface) || !($entity instanceof EntityChangedInterface) || !$entity->access('update')) {
          throw new AccessDeniedHttpException();
        }
        $updates[$entity_id] = $entity->getChangedTime();
      }
      else {
        // Entity was deleted.
        $updates[$entity_id] = FALSE;
      }
    }

    return new JsonResponse($updates);
  }

}
