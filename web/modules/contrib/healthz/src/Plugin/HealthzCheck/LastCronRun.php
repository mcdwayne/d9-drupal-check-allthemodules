<?php

namespace Drupal\healthz\Plugin\HealthzCheck;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\healthz\Plugin\HealthzCheckBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a check that cron has been running regularly.
 *
 * @HealthzCheck(
 *   id = "last_cron_run",
 *   title = @Translation("Last cron run"),
 *   description = @Translation("Checks cron has been running.")
 * )
 */
class LastCronRun extends HealthzCheckBase implements ContainerFactoryPluginInterface {

  /**
   * The request time.
   *
   * @var int
   */
  protected $requestTime;

  /**
   * The last cron run.
   *
   * @var int
   */
  protected $lastCronRun;

  /**
   * Create an instance of the LastCronRun health check.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $last_cron_run, $request_time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->lastCronRun = $last_cron_run;
    $this->requestTime = $request_time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('state')->get('system.cron_last'),
      $container->get('datetime.time')->getRequestTime()
    );
  }

  /**
   * {@inheritdoc}
   */
  public function check() {
    return $this->lastCronRun + $this->getFailureThreshold() > $this->requestTime;
  }

  /**
   * Get the failure threshold configured for this plugin.
   *
   * @return int
   *   The failure threshold in seconds. Defaults to 2 days.
   */
  private function getFailureThreshold() {
    return isset($this->getConfiguration()['settings']['failure_threshold']) ? $this->getConfiguration()['settings']['failure_threshold'] : 172800;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      'failure_threshold' => [
        '#type' => 'select',
        '#title' => $this->t('Failure threshold'),
        '#description' => $this->t('Choose the failure threshold for this check. This is the amount of time after the last cron run that will trigger a check failure.'),
        '#required' => TRUE,
        '#options' => [
          3600 => $this->t('1 hour'),
          86400 => $this->t('1 day'),
          172800 => $this->t('2 days'),
        ],
        '#default_value' => $this->getFailureThreshold(),
      ],
    ];
  }

}
