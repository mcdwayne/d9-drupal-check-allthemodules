<?php

namespace Drupal\image_moderate\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for content_entity_example_contact entity.
 *
 * @ingroup content_entity_example
 */
class ImageModerateListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $build['description'] = [
      '#markup' => $this->t('Image Moderate Entity implements automatic image moderation.', [
        '@adminlink' => \Drupal::urlGenerator()
          ->generateFromRoute('image_moderate.settings'),
      ]),
    ];

    $build += parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the image moderate list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Image Moderate ID');
    $header['fid'] = $this->t('fid');
    $header['entity_uuid'] = $this->t('Entity Title');
    $header['entity_type'] = $this->t('Entity Type');
    $header['status'] = $this->t('Image Moderate Status');
    $header['reviewed_by'] = $this->t('Reviewed by');
    $header['reviewed'] = $this->t('Reviewed at');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\image_moderate\Entity\ImageModerate */
    $row['id'] = $entity->id();
    $row['fid'] = $entity->fid->value;
    $reviewer = $entity->reviewed_by->getValue()[0]['target_id'];
    $target = \Drupal::entityTypeManager()->getStorage($entity->entity_type->value)->loadByProperties(['uuid' => $entity->entity_uuid->value]);
    $row['entity_uuid'] = reset($target)->toLink();
    $row['entity_type'] = $entity->entity_type->value;
    $row['status'] = $entity->status->value;
    $row['reviewed_by'] = \Drupal::entityTypeManager()->getStorage('user')->load($reviewer)->toLink();
    $row['reviewed'] = $entity->reviewed->value;
    return $row + parent::buildRow($entity);
  }

}
