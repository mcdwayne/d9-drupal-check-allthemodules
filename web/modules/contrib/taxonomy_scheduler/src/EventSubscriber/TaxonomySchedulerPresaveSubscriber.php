<?php

namespace Drupal\taxonomy_scheduler\EventSubscriber;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\taxonomy\TermInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Class TaxonomySchedulerPresaveSubscriber.
 */
class TaxonomySchedulerPresaveSubscriber implements EventSubscriberInterface {

  /**
   * Config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * DateTime.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  private $dateTime;

  /**
   * CacheTagsInvalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  private $cacheTagsInvalidator;

  /**
   * TaxonomySchedulerPresaveSubscriber constructor.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The config.
   * @param \Drupal\Component\Datetime\TimeInterface $dateTime
   *   The Drupal time object.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *   The cachetags invalidator.
   */
  public function __construct(
    ImmutableConfig $config,
    TimeInterface $dateTime,
    CacheTagsInvalidatorInterface $cacheTagsInvalidator
  ) {
    $this->config = $config;
    $this->dateTime = $dateTime;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  /**
   * Hooks into presave for a term object.
   *
   * Sets published when set publish date has passed when
   * fields are present and filled.
   *
   * @param \Drupal\hook_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   The event.
   */
  public function termPresave(EntityPresaveEvent $event): void {
    $entity = $event->getEntity();

    if (!$entity instanceof TermInterface) {
      return;
    }

    $vocabularies = $this->config->get('vocabularies');

    if ($vocabularies === NULL) {
      return;
    }

    $fieldName = $this->config->get('field_name');

    if (empty($fieldName)) {
      return;
    }

    if (!\in_array($entity->bundle(), $vocabularies, TRUE)) {
      return;
    }

    if (!$entity->hasField($fieldName)) {
      return;
    }

    if ($entity->get($fieldName)->isEmpty()) {
      return;
    }

    $fieldValue = $entity->get($fieldName);

    if (!isset($fieldValue->date)) {
      return;
    }

    $date = $fieldValue->date;

    if (!$date instanceof DrupalDateTime) {
      return;
    }

    if ($date->getTimestamp() <= $this->dateTime->getCurrentTime()) {
      $entity->setPublished();
      $this->cacheTagsInvalidator->invalidateTags($entity->getCacheTags());
      return;
    }

    $entity->setUnpublished();
    $this->cacheTagsInvalidator->invalidateTags($entity->getCacheTags());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'termPresave',
    ];
  }

}
