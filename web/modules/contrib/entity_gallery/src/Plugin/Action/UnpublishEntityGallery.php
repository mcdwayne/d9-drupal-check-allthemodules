<?php

namespace Drupal\entity_gallery\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Unpublishes an entity gallery.
 *
 * @Action(
 *   id = "entity_gallery_unpublish_action",
 *   label = @Translation("Unpublish selected content"),
 *   type = "entity_gallery"
 * )
 */
class UnpublishEntityGallery extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    $entity->status = ENTITY_GALLERY_NOT_PUBLISHED;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $object */
    $access = $object->access('update', $account, TRUE)
      ->andIf($object->status->access('edit', $account, TRUE));

    return $return_as_object ? $access : $access->isAllowed();
  }

}
