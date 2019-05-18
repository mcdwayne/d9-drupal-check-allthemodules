<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "http404",
 *  label = @Translation("404 Errors"),
 *  description = "Checks the database log for 404 errors.",
 *  tags = {
 *   "performance",
 *   "content",
 *   "seo",
 *  }
 * )
 */
class Http404 extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Module Handler
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The date formatter service.
   *
   * @var DateFormatter
   */
  protected $dateFormatter;

  /**
   * CacheBackend constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $database, $module_handler, $date_formatter) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->database = $database;
    $this->moduleHandler = $module_handler;
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
      $container->get('database'),
      $container->get('module_handler'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();

    $config['window'] = 24 * 60 * 60;

    $config['count'] = 0;

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $form['window'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ignore 404s older than'),
      '#default_value' => $this->configuration['window'],
      '#field_suffix' => $this->t('seconds'),
      '#size' => 10,
      '#description' => $this->t('404 errors than this will not be examined by Healthcheck.'),
      '#required' => TRUE,
    ];

    $form['count'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of errors expected'),
      '#default_value' => $this->configuration['count'],
      '#size' => 10,
      '#description' => $this->t('The maximum number of 404 errors to expected in the period of time specified above.'),
      '#required' => TRUE,
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    if ($this->moduleHandler->moduleExists('dblog')) {
      $findings[] = $this->checkLogs();
    }
    else {
      $findings[] = $this->notPerformed($this->getPluginId());
    }

    return $findings;
  }

  protected function checkLogs() {
    $now = time();

    // @todo Use a DISTINCT on the path to count unique errors for the whole month?
    $threshold = $now - $this->configuration['window'];

    $query = $this->database->select('watchdog', 'wd');

    $query->addExpression('COUNT(wid)');

    $query->condition('wd.type', 'page not found');

    $query->condition('wd.timestamp', $threshold, '>');

    $result = $query->execute()->fetchField();

    $expected = $this->configuration['count'];
    $found = count($result);
    $formatted_interval =  $this->dateFormatter->formatInterval($this->configuration['window']);

    if ($found > $expected) {
      return $this->actionRequested($this->getPluginId(), [
          'expected' => $expected,
          'found' => $found,
          'interval' => $formatted_interval,
        ]);
    }

    return $this->noActionRequired($this->getPluginId(), [
        'expected' => $expected,
        'found' => $found,
        'interval' => $formatted_interval,
      ]);
  }
}
