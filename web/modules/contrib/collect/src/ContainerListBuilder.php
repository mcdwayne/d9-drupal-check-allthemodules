<?php
/**
 * @file
 * Contains \Drupal\collect\ContainerListBuilder.
 */

namespace Drupal\collect;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

class ContainerListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  protected function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    if ($entity->access('view') && $entity->hasLinkTemplate('canonical')) {
      $operations['view'] = array(
        'title' => $this->t('View'),
        'weight' => -100,
        'url' => $entity->urlInfo('canonical'),
      );
    }
    return $operations;
  }


}
