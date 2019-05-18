<?php


namespace Drupal\healthcheck\Plugin\healthcheck;


use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\Report;
use Drupal\healthcheck\Plugin\HealthcheckPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\healthcheck\Finding\FindingStatus;

/**
 * @Healthcheck(
 *  id = "cache_backend",
 *  label = @Translation("Cache Backend"),
 *  description = "Checks the current caching backend.",
 *  tags = {
 *   "performance",
 *  }
 * )
 */
class CacheBackend extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The default cache bin.
   */
  protected $cacheDefault;

  /**
   * CacheBackend constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $finding_service, $cache_default) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $finding_service);
    $this->cacheDefault = $cache_default;
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
      $container->get('cache.default')
    );
  }

  public function getFindings() {
    $findings = [];

    // Get the default cache bin class name.
    $cache_default = get_class($this->cacheDefault);

    if (strpos($cache_default, 'DatabaseBackend') === FALSE) {
      $findings[] = $this->noActionRequired('cache_backend');
    }
    else {
      $findings[] = $this->actionRequested('cache_backend');
    }

    return $findings;
  }

}
