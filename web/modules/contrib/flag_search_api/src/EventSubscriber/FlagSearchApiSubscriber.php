<?php

namespace Drupal\flag_search_api\EventSubscriber;

use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag_search_api\FlagSearchApiReindexService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class FlagSearchApiSubscriber.
 */
class FlagSearchApiSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\flag_search_api\FlagSearchApiReindexService
   */
  protected $flagSearchApiReindex;

  /**
   * Constructs a new FlagSearchApiSubscriber object.
   *
   * @param \Drupal\flag_search_api\FlagSearchApiReindexService $flag_search_api_reindex_service
   *   FlagSearchApiReindexService.
   */
  public function __construct(FlagSearchApiReindexService $flag_search_api_reindex_service) {
    $this->flagSearchApiReindex = $flag_search_api_reindex_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['flag.entity_flagged'] = ['flagEntityFlagged'];
    $events['flag.entity_unflagged'] = ['flagEntityUnflagged'];

    return $events;
  }

  /**
   * Method is called whenever the flag.entity_flagged event is dispatched.
   *
   * @param \Drupal\flag\Event\FlaggingEvent $event
   *   Event.
   */
  public function flagEntityFlagged(FlaggingEvent $event) {
    $this->flagSearchApiReindex->reindexItem($event->getFlagging());
  }

  /**
   * Method is called whenever the flag.entity_unflagged event is dispatched.
   *
   * @param \Drupal\flag\Event\UnflaggingEvent $event
   *   Event.
   */
  public function flagEntityUnflagged(UnflaggingEvent $event) {
    $flaggings = $event->getFlaggings();
    /** @var \Drupal\flag\FlaggingInterface $flagging */
    foreach ($flaggings as $flagging) {
      $this->flagSearchApiReindex->reindexItem($flagging);
    }
  }

}
