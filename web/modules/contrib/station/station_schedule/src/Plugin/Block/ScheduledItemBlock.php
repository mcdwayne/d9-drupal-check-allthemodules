<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Plugin\Block\ScheduledItemBlock.
 */

namespace Drupal\station_schedule\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\station_schedule\ScheduleRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo.
 *
 * @Block(
 *   id = "station_schedule_item",
 *   admin_label = @Translation("Scheduled item"),
 *   context = {
 *     "schedule item" = @ContextDefinition("entity:station_schedule_item", required = FALSE)
 *   }
 * )
 */
class ScheduledItemBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\station_schedule\ScheduleRepositoryInterface
   */
  protected $scheduleRepository;

  /**
   * ScheduledItemBlock constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\station_schedule\ScheduleRepositoryInterface $schedule_repository
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $view_builder
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ScheduleRepositoryInterface $schedule_repository, EntityViewBuilderInterface $view_builder) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->scheduleRepository = $schedule_repository;
    $this->scheduleItemViewBuilder = $view_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('station_schedule.schedule.repository'),
      $container->get('entity_type.manager')->getViewBuilder('station_schedule_item')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if ($item = $this->getContextValue('schedule item')) {
      return $this->scheduleItemViewBuilder->view($item);
    }
    else {
      return [
        '#theme' => 'station_schedule_item_empty',
        '#time' => $this->t('For now...'),
        '#program' => $this->scheduleRepository->getCurrentSchedule()->getUnscheduledMessage(),
        '#djs' => $this->t('Nobody'),
        '#genre' => $this->t('Random music'),
      ];
    }
  }

}
