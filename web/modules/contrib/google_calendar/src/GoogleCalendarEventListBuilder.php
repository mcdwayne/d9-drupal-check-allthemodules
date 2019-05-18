<?php

namespace Drupal\google_calendar;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Google Calendar Event entities.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarEventListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\google_calendar\Entity\GoogleCalendarEvent */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.google_calendar_event.edit_form',
      ['google_calendar_event' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
