<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "cron",
 *  label = @Translation("Cron"),
 *  description = "Checks when cron was last run.",
 *  tags = {
 *   "performance",
 *  }
 * )
 */
class Cron extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The state service
   */
  protected $stateSrv;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * CacheBackend constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $state_srv, $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->stateSrv = $state_srv;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('state'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();

    $config['cron_offset'] = 24 * 60 * 60;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['cron_offset'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cron threshold'),
      '#default_value' => $this->configuration['cron_offset'],
      '#field_suffix' => $this->t('seconds'),
      '#size' => 10,
      '#description' => $this->t('The time after which cron is considered late and Healthcheck will mark it as Action Requested.'),
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    $form_values = $form_state->getValues();

    if ($form_values['cron_offset'] < 0) {
      $form_state->setErrorByName('cron_offset', $this->t('The cron threshold cannot be negative.'));
    }

    if (!is_numeric($form_values['cron_offset'])) {
      $form_state->setErrorByName('cron_offset', $this->t('The cron threshold must be a number.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];
    $now = \Drupal::time()->getRequestTime();
    $key = 'cron_last';

    $last_cron_run = $this->stateSrv->get('system.cron_last');
    $cron_offset = $now - $last_cron_run;

    $formatted_interval =  $this->dateFormatter->formatInterval($this->configuration['cron_offset']);

    if (empty($last_cron_run)) {
      // Never ran or no value.
      $findings[] = $this->critical($key, [
        'interval' => $formatted_interval,
      ]);
    }
    elseif ($cron_offset > $this->configuration['cron_offset']) {
      // Ran more than our cron offset ago.
      $findings[] = $this->actionRequested($key, [
        'interval' => $formatted_interval,
      ]);
    }
    else {
      // We can't check for less-than values as this check can be invoked
      // *during* a cron run, rendering the less-than always true.
      $findings[] = $this->noActionRequired($key, [
        'interval' => $formatted_interval,
      ]);
    }

    return $findings;
  }

}
