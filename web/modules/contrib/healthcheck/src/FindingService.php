<?php

namespace Drupal\healthcheck;
use Drupal\Component\Discovery\YamlDirectoryDiscovery;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\healthcheck\Finding\Finding;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\Plugin\HealthcheckPluginManager;

/**
 * Class FindingService.
 */
class FindingService implements FindingServiceInterface {

  use StringTranslationTrait;

  /**
   * Drupal\Core\Extension\ModuleHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * Drupal\Core\Extension\ThemeHandlerInterface definition.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * The HealthcheckPluginManager
   *
   * @var HealthcheckPluginManager;
   */
  protected $healthcheck_plugin_mgr;

  /**
   * The YAML directory discovery.
   *
   * @var null|YamlDirectoryDiscovery
   */
  protected $discovery = NULL;

  /**
   * The Drupal cache.
   *
   * @var CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new FindingService object.
   */
  public function __construct(ModuleHandlerInterface $module_handler,
                              ThemeHandlerInterface $theme_handler,
                              HealthcheckPluginManager $healthcheck_plugin_mgr,
                              CacheBackendInterface $cache) {
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
    $this->healthcheck_plugin_mgr = $healthcheck_plugin_mgr;
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function build($check, $key, $status, $data = []) {
    // Create a stub finding with the given parameters.
    $finding = new Finding($status, $check, $key, '', $data);

    if ($label = $this->getLabel($key, $status, $data)) {
      $finding->setLabel($label);
    }

    if ($message = $this->getMessage($key, $status, $data)) {
      $finding->setMessage($message);
    }

    return $finding;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel($key, $status, $data = []) {
    $findings = $this->getFindings();
    if (empty($findings[$key])) {
      return FALSE;
    }

    $statuses = FindingStatus::getTextConstants();
    $status_key = $statuses[$status];

    if (!empty($findings[$key][$status_key]['label'])) {
      $label = (string) $findings[$key][$status_key]['label'];
      $placeholders = $this->labelify($data);

      return $this->t($label, $placeholders);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage($key, $status, $data = []) {
    $findings = $this->getFindings();
    if (empty($findings[$key])) {
      return FALSE;
    }

    $statuses = FindingStatus::getTextConstants();
    $status_key = $statuses[$status];

    if (!empty($findings[$key][$status_key]['message'])) {
      $message = (string) $findings[$key][$status_key]['message'];
      $placeholders = $this->labelify($data);

      return $this->t($message, $placeholders);
    }

    return FALSE;
  }

  /**
   * Get the finding YAML as an array.
   *
   * @return array
   *   An array of findings keyed by finding key.
   */
  protected function getFindings() {
    // Get the findings from static cache if set.
    $findings = &drupal_static(__METHOD__, []);
    if (!empty($findings)) {
      return $findings;
    }

    // Check if we have cached findings.
    $cache = $this->cache->get('healthcheck.findings');

    // No? Load 'em up.
    if (empty($cache)) {
      // Discover all of the YAML files.
      $discovery = $this->getDiscovery();

      // Parse them all.
      $files = $discovery->findAll();

      // Merge the found files together keyed by ID.
      foreach ($files as $module => $items) {
        foreach ($items as $item) {
          $id = $item['id'];

          if (empty($findings[$id])) {
            $findings[$id] = $item;
          }
        }
      }

      // Allow modules to alter them.
      $this->moduleHandler->alter('healthcheck_findings', $findings);

      // Save the result to cache.
      $this->cache->set('healthcheck.findings', $findings, CacheBackendInterface::CACHE_PERMANENT);
    }
    else {
      // Cache get? Get the files.
      $findings = $cache->data;
    }

    return $findings;
  }

  /**
   * Discover any YAML directories used for healthcheck findings.
   *
   * @return \Drupal\Component\Discovery\YamlDirectoryDiscovery
   *   A new YamlDirectoryDiscovery for healthcheck findings.
   */
  protected function getDiscovery() {
    // This was based on a 8.7 issue on help_topics.
    // @see https://www.drupal.org/project/drupal/issues/2920309

    if (empty($this->discovery)) {
      $directories = array_merge($this->moduleHandler->getModuleDirectories(), $this->themeHandler->getThemeDirectories());

      $directories = array_map(function ($dir) {
        return [$dir . '/healthcheck_finding'];
      }, $directories);

      $file_cache_key_suffix = 'healthcheck_finding';
      $id_key = 'id';
      $this->discovery = new YamlDirectoryDiscovery($directories, $file_cache_key_suffix, $id_key);
    }

    return $this->discovery;
  }

  /**
   * Labelifies the data array for use as text placeholders
   *
   * @param $data
   *   The data array to labelify.
   *
   * @return array
   *   The labelified array
   */
  protected function labelify($data) {
    $out = [];

    // If the data isn't an array, return an empty array..
    if (!is_array($data)) {
      return [];
    }

    foreach ($data as $key => $value) {
      // Make the key a placeholder if it isn't already one.
      if (!in_array(substr($key, 0, 1), ['@', '%', ':'])) {
        $label_key = '@' . $key;
      }
      else {
        $label_key = $key;
      }

      $out[$label_key] = is_scalar($value) ? $value : print_r($value, TRUE);
    }

    return $out;
  }
}
