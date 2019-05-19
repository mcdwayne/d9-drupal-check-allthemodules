<?php

namespace Drupal\tmgmt_memory\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides the views data for the Usage entity type.
 */
class UsageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    /** @var \Drupal\tmgmt_memory\UsageInterface $entity */
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')
      && $entity->getJobItemId() && $entity->getDataItemKey() && $entity->getSegmentDelta()) {
      $operations['view'] = [
        'title' => $this->t('View'),
        'weight' => -10,
        'url' => $entity->toUrl('canonical'),
      ];
    }
    return $operations;
  }

}
