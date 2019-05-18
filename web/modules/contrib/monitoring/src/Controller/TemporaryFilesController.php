<?php

namespace Drupal\monitoring\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;
use Drupal\monitoring\SensorConfigInterface;
use Drupal\monitoring\SensorRunner;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TemporaryFilesController extends ControllerBase {

  /**
   * The sensor runner.
   *
   * @var \Drupal\monitoring\SensorRunner
   */
  protected $sensorRunner;

  /**
   * Constructs a \Drupal\monitoring\Controller\TemporaryFilesController object.
   *
   * @param \Drupal\monitoring\SensorRunner $sensor_runner
   *   The sensor runner service.
   */
  public function __construct(SensorRunner $sensor_runner) {
    $this->sensorRunner = $sensor_runner;
  }

  /**
   * {@inheritdoc
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('monitoring.sensor_runner')
    );
  }

  /**
   * Makes a file permanent.
   *
   * @param \Drupal\monitoring\SensorConfigInterface $monitoring_sensor_config
   *   The sensor config.
   * @param \Drupal\file\FileInterface $file
   *   The file entity.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function makePermanent(SensorConfigInterface $monitoring_sensor_config, FileInterface $file) {
    $file->setPermanent();
    $file->save();
    $this->sensorRunner->resetCache([$monitoring_sensor_config->id()]);
    drupal_set_message(t('File @file is now permanent.', [
      '@file' => $file->getFilename(),
    ]), 'status');
    $url = $monitoring_sensor_config->urlInfo('details-form');
    return $this->redirect($url->getRouteName(), $url->getRouteParameters());
  }
}
