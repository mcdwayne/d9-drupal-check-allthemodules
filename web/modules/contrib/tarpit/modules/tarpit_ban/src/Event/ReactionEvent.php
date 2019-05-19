<?php

/**
 * @file
 * Contains \Drupal\tarpit_ban\Event\ReactionEvent.
 */
namespace Drupal\tarpit_ban\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

class ReactionEvent extends GenericEvent {
  const EVENT_NAME = 'tarpit_ban.reaction';
}
