<?php

namespace Drupal\entity_collector\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Class EntityCollectionActionController
 *
 * @package Drupal\entity_collector\Controller
 */
class EntityCollectionActionController extends EntityCollectionControllerBase implements ContainerInjectionInterface {

  /**
   * Add entities to the collection.
   *
   * @param int $entityCollectionId
   * @param int $entityId
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Drupal\Core\Ajax\AjaxResponse
   *   The response.
   * @throws \Exception
   */
  public function addItemToCollection($entityCollectionId, $entityId) {
    $request = $this->requestStack->getCurrentRequest();
    $response = new RedirectResponse($request->headers->get('referer'));
    $lockName = 'entity_collection_action_' . $entityCollectionId;
    $this->entityCollectionManager->acquireLock($lockName);

    try {
      $entityCollection = $this->entityCollectionManager->getEntityCollection($entityCollectionId);
      $this->entityCollectionManager->addItemToCollection($entityCollection, $entityId);
      if ($request->isXmlHttpRequest()) {
        $response = new AjaxResponse();
        $selector = '.entity-collection-item-' . $entityId . '.entity-collection-type-' . $entityCollection->bundle();
        $response->addCommand(new InvokeCommand($selector . '.js-entity-collection-action-add', 'addClass', ['visually-hidden']));
        $response->addCommand(new InvokeCommand($selector . '.js-entity-collection-action-remove', 'removeClass', ['visually-hidden']));
        $response->addCommand(new InvokeCommand('body', 'trigger', [
          'addItemToCollection',
          [$entityCollection->bundle(), $entityCollection->id(), $entityId],
        ]));
      }
    } finally {
      $this->entityCollectionManager->releaseLock($lockName);
    }

    return $response;
  }

  /**
   * Remove the entity from a collection.
   *
   * @param int $entityCollectionId
   * @param int $entityId
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Drupal\Core\Ajax\AjaxResponse
   *   The response.
   * @throws \Exception
   */
  public function removeItemFromCollection($entityCollectionId, $entityId) {
    $request = $this->requestStack->getCurrentRequest();
    $response = new RedirectResponse($request->headers->get('referer'));
    $lockName = 'entity_collection_action_' . $entityCollectionId;
    $this->entityCollectionManager->acquireLock($lockName);
    try {
      $entityCollection = $this->entityCollectionManager->getEntityCollection($entityCollectionId);
      $this->entityCollectionManager->removeItemFromCollection($entityCollection, $entityId);

      if ($request->isXmlHttpRequest()) {
        $response = new AjaxResponse();
        $selector = '.entity-collection-item-' . $entityId . '.entity-collection-type-' . $entityCollection->bundle();
        $response->addCommand(new InvokeCommand($selector . '.js-entity-collection-action-remove', 'addClass', ['visually-hidden']));
        $response->addCommand(new InvokeCommand($selector . '.js-entity-collection-action-add', 'removeClass', ['visually-hidden']));
        $response->addCommand(new InvokeCommand('body', 'trigger', [
          'removeItemFromCollection',
          [$entityCollection->bundle(), $entityCollection->id(), $entityId],
        ]));
      }
    } finally {
      $this->entityCollectionManager->releaseLock($lockName);
    }

    return $response;
  }

  /**
   * Remove the participant from a collection.
   *
   * @param int $entityCollectionId
   *
   * @return RedirectResponse|AjaxResponse
   *   The response.
   * @throws \Exception
   */
  public function removeCurrentUserFromCollection($entityCollectionId) {
    $request = $this->requestStack->getCurrentRequest();
    $response = new RedirectResponse($request->headers->get('referer'));

    $lockName = 'entity_collection_participant_action_' . $entityCollectionId;
    $this->entityCollectionManager->acquireLock($lockName);

    try {
      $entityCollection = $this->entityCollectionManager->getEntityCollection($entityCollectionId);
      $this->entityCollectionManager->removeParticipantFromCollection($entityCollection, $this->currentUser);

      if ($request->isXmlHttpRequest()) {
        $response = new AjaxResponse();
        $response->addCommand(new InvokeCommand('body', 'trigger', [
          'entityCollectionParticipantRemoval',
          [$entityCollection->bundle(), $entityCollection->id()],
        ]));
      }
    } finally {
      $this->entityCollectionManager->releaseLock($lockName);
    }

    return $response;
  }

}
