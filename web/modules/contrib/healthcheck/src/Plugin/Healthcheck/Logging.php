<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "logging",
 *  label = @Translation("Logging"),
 *  description = "Checks the logging configuration.",
 *  tags = {
 *   "performance",
 *   "infrastructure",
 *  }
 * )
 */
class Logging extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The Module Handler
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * CacheBackend constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $module_handler) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->moduleHandler = $module_handler;
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
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    $dblog = $this->moduleHandler->moduleExists('dblog');
    $syslog = $this->moduleHandler->moduleExists('syslog');

    if ($dblog && $syslog) {
      $findings[] = $this->actionRequested('logging.both');
    }
    elseif ($dblog) {
      $findings[] = $this->needsReview('logging.dblog');
    }
    elseif ($syslog) {
      $findings[] = $this->noActionRequired('logging.syslog');
    }
    else {
      $findings[] = $this->actionRequested('logging.none');
    }

    return $findings;
  }
}
