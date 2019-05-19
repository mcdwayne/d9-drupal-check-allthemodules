<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Routing\ScheduleRouteProvider.
 */

namespace Drupal\station_schedule\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\station_schedule\Form\ScheduleSettingsForm;
use Drupal\station_schedule\ScheduleRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * @todo.
 */
class ScheduleRouteProvider implements ContainerInjectionInterface {

  /**
   * @var \Drupal\station_schedule\ScheduleRepositoryInterface
   */
  protected $scheduleRepository;

  /**
   * ScheduleRouteProvider constructor.
   *
   * @param \Drupal\station_schedule\ScheduleRepositoryInterface $schedule_repository
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ScheduleRepositoryInterface $schedule_repository, ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    $this->scheduleRepository = $schedule_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('station_schedule.schedule.repository'),
      $container->get('config.factory')
    );
  }

  public function routes() {
    $routes = [];

    $routes['station_schedule.settings'] = (new Route('/admin/station/schedule/settings'))
      ->addDefaults([
        '_form' => ScheduleSettingsForm::class,
        '_title' => 'Settings',
      ])
      ->setRequirement('_permission', 'administer station schedule');

    $provide_route = $this->configFactory->get('station_schedule.settings')->get('provide_schedule_route');
    if ($provide_route && $current_schedule_id = $this->scheduleRepository->getCurrentScheduleId()) {
      $routes['station_schedule.current_schedule'] = (new Route('/schedule'))
        ->addDefaults([
          '_entity_view' => 'station_schedule.full',
          'station_schedule' => $current_schedule_id,
          '_title_callback' => '\Drupal\Core\Entity\Controller\EntityController::title',
        ])
        ->setRequirement('_entity_access', 'station_schedule.view')
        ->setOption('parameters', [
          'station_schedule' => ['type' => 'entity:station_schedule'],
        ]);
    }
    return $routes;
  }

}
