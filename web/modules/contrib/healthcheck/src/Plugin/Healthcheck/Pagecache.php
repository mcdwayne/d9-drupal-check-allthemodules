<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @Healthcheck(
 *  id = "pagecache",
 *  label = @Translation("Page Caching"),
 *  description = "Checks page caching configuration.",
 *  tags = {
 *   "performance",
 *  }
 * )
 */
class Pagecache extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Pagecache constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFindings() {
    $findings = [];

    $config = $this->configFactory->get('system.performance');
    $max_age = $config->get('cache.page.max_age');

    if ($max_age <= 0) {
      $findings[] = $this->needsReview('max_age');
    }
    elseif ($max_age < 900) {
      $findings[] = $this->actionRequested('max_age');
    }
    else {
      $findings[] = $this->noActionRequired('max_age');
    }

    return $findings;
  }

}
