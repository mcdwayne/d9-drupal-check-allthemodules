<?php

namespace Drupal\google_calendar;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of Google Calendar entities.
 *
 * @ingroup google_calendar
 */
class GoogleCalendarListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {

    $header['name'] = $this->t('Name');
    $header['id'] = $this->t('Google Calendar ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\google_calendar\Entity\GoogleCalendar */

    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'view.google_calendar_events.calendar_list',
      ['google_calendar' => $entity->id()]
    );
    //$row['name'] = $entity->label();
    $row['id'] = $entity->getGoogleCalendarId();
    return $row + parent::buildRow($entity);
  }

}
