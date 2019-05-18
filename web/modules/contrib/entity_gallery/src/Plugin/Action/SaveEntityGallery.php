<?php

namespace Drupal\entity_gallery\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an action that can save any entity.
 *
 * @Action(
 *   id = "entity_gallery_save_action",
 *   label = @Translation("Save content"),
 *   type = "entity_gallery"
 * )
 */
class SaveEntityGallery extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function execute($entity = NULL) {
    // We need to change at least one value, otherwise the changed timestamp
    // will not be updated.
    $entity->changed = 0;
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $object */
    return $object->access('update', $account, $return_as_object);
  }

}
