<?php

namespace Drupal\entity_gallery\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides specific access control for the entity gallery entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:entity_gallery",
 *   label = @Translation("Entity gallery selection"),
 *   entity_types = {"entity_gallery"},
 *   group = "default",
 *   weight = 1
 * )
 */
class EntityGallerySelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['target_bundles']['#title'] = $this->t('Gallery types');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);
    // Adding the 'entity_gallery_access' tag is sadly insufficient for entity
    // galleries: core requires us to also know about the concept of 'published'
    // and 'unpublished'.
    if (!$this->currentUser->hasPermission('bypass entity gallery access')) {
      $query->condition('status', ENTITY_GALLERY_PUBLISHED);
    }
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function createNewEntity($entity_type_id, $bundle, $label, $uid) {
    $entity_gallery = parent::createNewEntity($entity_type_id, $bundle, $label, $uid);

    // In order to create a referenceable entity gallery, it needs to published.
    /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
    $entity_gallery->setPublished(TRUE);

    return $entity_gallery;
  }

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableNewEntities(array $entities) {
    $entities = parent::validateReferenceableNewEntities($entities);
    // Mirror the conditions checked in buildEntityQuery().
    if (!$this->currentUser->hasPermission('bypass entity gallery access')) {
      $entities = array_filter($entities, function ($entity_gallery) {
        /** @var \Drupal\entity_gallery\EntityGalleryInterface $entity_gallery */
        return $entity_gallery->isPublished();
      });
    }
    return $entities;
  }

}
