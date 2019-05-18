<?php

namespace Drupal\flag_rating\EventSubscriber;

use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlagServiceInterface;
use Drupal\flag\Event\FlagEvents as Flag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Custom flag events subscriber.
 */
class FlagEvents implements EventSubscriberInterface {

  /**
   * The flag service.
   *
   * @var \Drupal\flag\FlagServiceInterface
   */
  protected $flagService;

  /**
   * Constructor.
   *
   * @param \Drupal\flag\FlagServiceInterface $flag_service
   *   The flag service.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(FlagServiceInterface $flag_service, RequestStack $request_stack) {
    $this->flagService = $flag_service;
    $this->currentRequest = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[Flag::ENTITY_FLAGGED] = ['onFlag', 50];
    $events[Flag::ENTITY_UNFLAGGED] = ['onUnflag', 50];
    return $events;
  }

  /**
   * React to flagging event.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   The flagging event.
   */
  public function onFlag(FlaggingEvent $event) {
    $flag = $event->getFlagging()->getFlag();
    // $flag_id = $event->getFlagging()->getFlagId();
    if ($flag->getLinkTypePlugin()->getPluginId() == 'ajax_rating') {
      if ($score_field = $flag->getThirdPartySetting('flag_rating', 'score_field', NULL)) {
        if ($rating = $this->getRating()) {
          $flagging = $event->getFlagging();
          \Drupal::logger('eventman')->info($flagging->id());
          $flagging->set($score_field, $rating);
          $flagging->save();
        }
      }
    }
  }
  
  /**
   * React to unflagging event.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   The unflagging event.
   */
  public function onUnflag(UnflaggingEvent $event) {
    // Nothing special
  }

  /**
   * Helper function to get rating from request.
   *
   * @param Request $request
   * @return int
   *    The rating as a number. Zero if rating not found.
   */
  protected function getRating() {
    return (int) $this->currentRequest->query->get('rating');
  }

}
