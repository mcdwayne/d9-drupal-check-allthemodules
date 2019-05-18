<?php

namespace Drupal\erf;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Participant entities.
 *
 * @ingroup erf
 */
class ParticipantListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Participant ID');
    $header['name'] = $this->t('Participant');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\erf\Entity\Participant */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.participant.edit_form',
      ['participant' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
