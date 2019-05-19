<?php

/**
 * @file
 * Contains \Drupal\station_schedule\ContextProvider\ScheduledItemContext.
 */

namespace Drupal\station_schedule\ContextProvider;

use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\station_schedule\ScheduleRepositoryInterface;

/**
 * @todo.
 */
class ScheduledItemContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * @var \Drupal\station_schedule\ScheduleRepositoryInterface
   */
  protected $scheduleRepository;

  /**
   * ScheduledItemContext constructor.
   *
   * @param \Drupal\station_schedule\ScheduleRepositoryInterface $schedule_repository
   */
  public function __construct(ScheduleRepositoryInterface $schedule_repository) {
    $this->scheduleRepository = $schedule_repository;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    return [
      'current_schedule_item' => new Context(new ContextDefinition('entity:station_schedule_item', $this->t('Current schedule item')), $this->scheduleRepository->getCurrentScheduleItem()),
      'next_schedule_item' => new Context(new ContextDefinition('entity:station_schedule_item', $this->t('Next schedule item')), $this->scheduleRepository->getNextScheduleItem()),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    return [
      'current_schedule_item' => new Context(new ContextDefinition('entity:station_schedule_item', $this->t('Current schedule item'))),
      'next_schedule_item' => new Context(new ContextDefinition('entity:station_schedule_item', $this->t('Next schedule item'))),
    ];
  }

}
