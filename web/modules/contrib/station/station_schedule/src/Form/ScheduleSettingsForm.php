<?php

/**
 * @file
 * Contains \Drupal\station_schedule\Form\ScheduleSettingsForm.
 */

namespace Drupal\station_schedule\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo.
 */
class ScheduleSettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $scheduleStorage;

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * ScheduleSettingsForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $schedule_storage
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(EntityStorageInterface $schedule_storage, RouteBuilderInterface $route_builder, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->scheduleStorage = $schedule_storage;
    $this->configFactory = $config_factory;
    $this->routeBuilder = $route_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('station_schedule'),
      $container->get('router.builder'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'station_schedule_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $schedules = [];
    foreach ($this->scheduleStorage->loadMultiple() as $id => $schedule) {
      $schedules[$id] = $schedule->label();
    }

    if ($schedules) {
      $form['current_schedule'] = [
        '#type' => 'select',
        '#title' => $this->t('Current schedule'),
        '#options' => $schedules,
        '#default_value' => $this->config('station_schedule.settings')->get('current_schedule'),
      ];
    }
    else {
      $form['empty']['#markup'] = $this->t('No settings to configure.');
      $form['actions']['#disabled'] = TRUE;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('station_schedule.settings')
      ->set('current_schedule', $form_state->getValue('current_schedule'))
      ->save(TRUE);
    $this->routeBuilder->setRebuildNeeded();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['station_schedule.settings'];
  }

}
