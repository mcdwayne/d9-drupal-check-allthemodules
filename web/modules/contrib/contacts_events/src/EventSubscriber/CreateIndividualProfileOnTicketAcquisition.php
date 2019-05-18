<?php

namespace Drupal\contacts_events\EventSubscriber;

use Drupal\contacts_events\Event\TicketContactAcquisitionEvent;
use Drupal\profile\Entity\Profile;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber invoked on acquiring a contact during ticket creation.
 *
 * Handles setting up the individual profile on newly created users.
 *
 * @package Drupal\contacts_events\EventSubscriber
 */
class CreateIndividualProfileOnTicketAcquisition implements EventSubscriberInterface {

  /**
   * Invoked after an acquisition has ocurred after a ticket has been added.
   *
   * @param \Drupal\contacts_events\Event\TicketContactAcquisitionEvent $event
   *   The event representing the acquisition.
   */
  public function onAcquisition(TicketContactAcquisitionEvent $event) {
    // No action if ticket has no email.
    if (empty($event->ticket->get('email')->value)) {
      return;
    }

    /* @var Profile $profile */
    $profile = $event->user->profile_crm_indiv->entity
      ?? Profile::create(['type' => 'crm_indiv', 'uid' => $event->user->id()]);

    $is_create = $event->acquisitionMethod == 'create';

    if ($is_create || empty($event->user->getEmail())) {
      $event->user->setEmail($event->ticket->get('email')->value);
      $event->user->addRole('crm_indiv');
      $event->entitiesToSave[] = $event->user;
    }

    if ($is_create || empty($profile->get('crm_dob')->value)) {
      $profile->set('crm_dob', $event->ticket->get('date_of_birth')->value);
    }

    if ($is_create || empty($profile->get('crm_name')->value)) {
      $profile->set('crm_name', $event->ticket->get('name')->getValue());
    }

    $event->entitiesToSave[] = $profile;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[TicketContactAcquisitionEvent::NAME][] = ['onAcquisition'];
    return $events;
  }

}
