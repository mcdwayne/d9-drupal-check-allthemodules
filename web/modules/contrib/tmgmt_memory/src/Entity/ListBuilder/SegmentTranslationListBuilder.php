<?php

namespace Drupal\tmgmt_memory\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides the views data for the SegmentTranslation entity type.
 */
class SegmentTranslationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    /** @var \Drupal\tmgmt_memory\SegmentTranslationInterface $entity */
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = array(
        'title' => $this->t('View'),
        'weight' => -10,
        'url' => $entity->toUrl('canonical'),
      );
    }
    if (!$entity->getState() && $entity->access('edit')) {
      $operations['enable'] = array(
        'title' => $this->t('Enable'),
        'weight' => 0,
        'url' => $entity->toUrl('change-state'),
      );
    }
    elseif ($entity->access('edit')) {
      $operations['disable'] = array(
        'title' => $this->t('Disable'),
        'weight' => 0,
        'url' => $entity->urlInfo('change-state'),
      );
    }
    return $operations;
  }

}
