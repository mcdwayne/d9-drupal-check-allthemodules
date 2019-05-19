<?php

namespace Drupal\strava_activities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of Activity entities.
 *
 * @ingroup strava
 */
class ActivityListBuilder extends EntityListBuilder {

  use LinkGeneratorTrait;

  /**
   * @var int
   */
  protected $limit = 25;

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Activity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\strava_activities\Entity\Activity */
    $row['id'] = $entity->id();
    $url = new Url('entity.activity.edit_form', ['activity' => $entity->id()]);
    $row['name'] = new Link($entity->label(), $url);
    return $row + parent::buildRow($entity);
  }

}
