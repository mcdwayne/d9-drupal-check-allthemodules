<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Membership entities.
 *
 * @ingroup membership_entity
 */
class MembershipListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Membership ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\membership_entity\Entity\MembershipEntity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.membership_entity.edit_form',
      ['membership_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

  /**
   * @inheritDoc
   */
  public function render() {
    $build = parent::render();
    $build['table']['#empty'] = $this->t('There are no Memberships yet.');
    return $build;
  }

  /**
   * @inheritDoc
   */
  protected function getTitle() {
    return $this->t('Memberships');
  }

}
