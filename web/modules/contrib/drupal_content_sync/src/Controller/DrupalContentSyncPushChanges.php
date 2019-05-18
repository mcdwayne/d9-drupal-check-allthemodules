<?php

namespace Drupal\drupal_content_sync\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\drupal_content_sync\Entity\Flow;
use Drupal\drupal_content_sync\ExportIntent;
use Drupal\drupal_content_sync\SyncIntent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Push changes controller.
 */
class DrupalContentSyncPushChanges extends ControllerBase {

  /**
   * Published entity to API Unify.
   *
   * @param string $flow_id
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   * @param string $entity_type
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function pushChanges($flow_id, $entity, $entity_type = '') {

    if (!$entity instanceof FieldableEntityInterface) {
      if ($entity_type == '') {
        throw new \Exception(t('If no entity object is given, the bundle is requried.'));
      }
      $entity = \Drupal::entityTypeManager()->getStorage($entity_type)->load($entity);
      if (!$entity instanceof FieldableEntityInterface) {
        throw new \Exception(t('Entity could not be loaded.'));
      }
    }

    $flow = Flow::load($flow_id);
    if (!ExportIntent::exportEntityFromUi(
      $entity,
      ExportIntent::EXPORT_MANUALLY,
      SyncIntent::ACTION_UPDATE,
      $flow
    )) {
      $messenger = \Drupal::messenger();
      $messenger->addWarning(t('%label is not configured to be exported with Drupal Content Sync.', ['%label' => $entity->label()]));
    }

    return new RedirectResponse('/');
  }

  /**
   * Returns an read_list entities for API Unify.
   *
   * TODO Should be removed when read_list will be allowed to omit.
   */
  public function pushChangesEntitiesList() {
    return new Response('[]');
  }

}
