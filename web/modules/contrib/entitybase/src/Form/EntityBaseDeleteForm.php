<?php

/**
 * @file
 * Contains \Drupal\entity_base\Form\EntityBaseDeleteForm.
 */

namespace Drupal\entity_base\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;

/**
 * Provides a form for deleting an entity.
 */
class EntityBaseDeleteForm extends ContentEntityDeleteForm {

  /**
   * {@inheritdoc}
   */
  protected function getDeletionMessage() {
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $entity_type_name = $entity_type->getLabel();

    // $entity_type_storage = $this->entityTypeManager->getStorage($entity_type_id . '_type');
    // $bundle_name = $entity_type_storage->load($entity->bundle())->label();
    // @todo implement bundle
    $entity_bundle_name = '';

    if (!$entity->isDefaultTranslation()) {
      return $this->t('@language translation of the @type %label has been deleted.', [
        '@language' => $entity->language()->getName(),
        '@type' => $entity_type_name,
        '%label' => $entity->label(),
      ]);
    }

    return $this->t('The @type @bundle %title has been deleted.', array(
      '@type' => $entity_type_name,
      '@bundle' => $entity_bundle_name,
      '%title' => $this->getEntity()->label(),
    ));
  }

  /**
   * {@inheritdoc}
   */
  protected function logDeletionMessage() {
    $entity = $this->getEntity();
    $entity_type = $entity->getEntityType();
    $entity_type_id = $entity_type->id();
    $entity_type_name = $entity_type->getLabel();
    // @todo implement bundle
    $entity_bundle_name = '';
    $this->logger($entity_type_id)->notice('@type: @bundle: deleted %title.', ['@type' => $entity_type_name, '@bundle' => $entity_bundle_name, '%title' => $entity->label()]);
  }

}
