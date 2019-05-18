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
 *  id = "preprocess",
 *  label = @Translation("CSS and JS pre-processing"),
 *  description = "Checks if CSS and JS pre-processing is enabled.",
 *  tags = {
 *   "performance",
 *  }
 * )
 */
class PreProcessing extends HealthcheckPluginBase  implements ContainerFactoryPluginInterface {

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

  public function getFindings() {
    $config = $this->configFactory->get('system.performance');
    $findings = [];

    // Check if CSS preprocessing is enabled.
    if ($css = $config->get('css.preprocess')) {
      $findings[] = $this->noActionRequired('preprocess.css');
    }
    else {
      $findings[] = $this->actionRequested('preprocess.css');
    }

    // Check if JS preprocessing is enabled.
    if ($js = $config->get('js.preprocess')) {
      $findings[] = $this->noActionRequired('preprocess.js');
    }
    else {
      $findings[] = $this->actionRequested('preprocess.js');
    }

    return $findings;
  }

}
