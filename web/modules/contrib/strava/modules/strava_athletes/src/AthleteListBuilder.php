<?php

namespace Drupal\strava_athletes;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Athlete entities.
 *
 * @ingroup strava
 */
class AthleteListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * @var int
   */
  protected $limit = 25;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Athlete ID');
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\strava_athletes\Entity\Athlete */
    $row['id'] = $entity->id();
    $row['label'] = $this->l(
      $entity->label(),
      new Url(
        'entity.athlete.edit_form', [
          'athlete' => $entity->id(),
        ]
      )
    );
    return $row + parent::buildRow($entity);
  }

}
