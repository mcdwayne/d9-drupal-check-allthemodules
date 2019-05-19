<?php

namespace Drupal\strava_clubs;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Club entities.
 *
 * @ingroup strava
 */
class ClubListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * @var int
   */
  protected $limit = 25;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Club ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\strava_clubs\Entity\Club */
    $row['id'] = $entity->id();
    $row['name'] = $this->l(
      $entity->label(),
      new Url(
        'entity.club.edit_form', [
          'club' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

}
