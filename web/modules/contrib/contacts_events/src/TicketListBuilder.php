<?php

namespace Drupal\contacts_events;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Defines a class to build a listing of Ticket entities.
 *
 * @ingroup contacts_events
 */
class TicketListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Ticket ID');
    $header['event'] = $this->t('Event');
    $header['booking'] = $this->t('Booking');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\contacts_events\Entity\Ticket */
    $row['id'] = $entity->id();

    $event = $entity->getEvent();
    $row['event'] = $event ? $event->toLink() : '';

    $booking = $entity->getBooking();
    $row['booking'] = $booking ? $booking->toLink() : '';

    $row['name'] = $entity->toLink($entity->getOrderItemTitle());
    return $row + parent::buildRow($entity);
  }

}
