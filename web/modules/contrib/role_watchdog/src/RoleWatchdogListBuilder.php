<?php

namespace Drupal\role_watchdog;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Role Watchdog entities.
 *
 * @ingroup role_watchdog
 */
class RoleWatchdogListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Role Watchdog ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\role_watchdog\Entity\RoleWatchdog */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.role_watchdog.edit_form',
      ['role_watchdog' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
