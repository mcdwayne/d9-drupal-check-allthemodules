<?php

namespace Drupal\strava_activities\Plugin\QueueWorker;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\Queue\QueueInterface;
use Drupal\strava\Api\Strava;

/**
 * Class StravaActivitiesQueue
 *
 * @QueueWorker(
 *   id = "strava_activities",
 *   title = @Translation("Strava activity detail synchronisation"),
 *   cron = {"time" = 30}
 * )
 *
 * @package Drupal\strava_activities\Plugin
 */
class StravaActivitiesQueue extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * The queue object.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * The Strava activities module configuration object.
   *
   * @var $config
   */
  protected $config;

  /**
   * Constructs a new StravaActivitiesQueue object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Queue\QueueInterface $queue
   *   The queue object.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, QueueInterface $queue) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->queue = $queue;

    $this->config = \Drupal::config('strava_activities_configuration.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('queue')->get('strava_activities', TRUE)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $activity_manager = \Drupal::service('strava.activity_manager');
    /** @var \Drupal\strava_activities\Entity\Activity $activity */
    $activity = $activity_manager->loadActivityByProperty('id', $data['id']);

    // Update the activity details after given time.
    $interval = $this->config->get('cron_sync_time') ? $this->config->get('cron_sync_time') : 86400;
    $time = \Drupal::time()->getRequestTime() - $interval;
    if ($activity && $activity->getChangedTime() > $time) {
      $strava = new Strava();
      $client = $strava->getApiClientForAthlete($data['athlete']['id']);
      if ($client) {
        $activity_details = $client->getActivity($data['id']);
        $activity_manager->updateActivity($activity_details);
      }
    }
  }

}
