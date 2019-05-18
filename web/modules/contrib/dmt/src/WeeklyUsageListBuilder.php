<?php

namespace Drupal\dmt;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Weekly usage entities.
 *
 * @ingroup dmt
 */
class WeeklyUsageListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Weekly usage ID');
    $header['module'] = $this->t('Module');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\dmt\Entity\WeeklyUsage */
    $row['id'] = $entity->id();
    $row['module'] = Link::createFromRoute(
      $entity->getModule()->label(),
      'entity.weekly_usage.edit_form',
      ['weekly_usage' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
