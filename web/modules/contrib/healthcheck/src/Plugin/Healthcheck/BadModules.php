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
 *  id = "bad_modules",
 *  label = @Translation("Unrecommended Modules"),
 *  description = "Checks for unrecommended and bad-practice modules.",
 *  tags = {
 *   "performance",
 *   "security",
 *  }
 * )
 */
class BadModules extends HealthcheckPluginBase implements ContainerFactoryPluginInterface {

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
    $patterns = $this->getPatterns();

    foreach ($patterns as $pattern) {
      $findings[] = $this->checkPattern($pattern);
    }

    return $findings;
  }

  /**
   * Checks an individual pattern against enabled modules.
   *
   * @param $pattern
   *   An individual pattern array.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *
   * @see \Drupal\healthcheck\Plugin\healthcheck\BadModules::getPatterns()
   */
  protected function checkPattern(&$pattern) {
    $matches = [];

    // Get a list of all modules.
    $module_names = $this->getModuleList();

    // Go through each module, attempting to match it against the pattern.
    foreach ($module_names as $module_name) {
      if (preg_match($pattern['pattern'], $module_name)) {
        $matches[] = $module_name;
      }
    }

    //
    if (!empty($matches)) {
      return $this->found($pattern['status'], $pattern['key'], [
        'modules' => $matches,
        'module_list' => implode(', ', $matches),
      ]);
    }

    // No matches? Then we're good.
    return $this->noActionRequired($pattern['key']);
  }

  /**
   * Gets a list of currently enabled modules.
   *
   * @return array
   *   An array of module names.
   */
  protected function getModuleList() {
    $module_names = &drupal_static(__METHOD__, []);

    if (empty($module_names)) {
      // Get the list of modules from the module handler.
      $modules = $this->moduleHandler->getModuleList();

      // Get the module names from the array keys.
      $module_names = array_keys($modules);
    }

    return $module_names;
  }

  /**
   * Gets a array of bad module name patterns with statuses and messages.
   *
   * @return array
   *   An array of bad modules.
   */
  protected function getPatterns() {
    $patterns = [];

    $patterns[] = [
      'pattern' => '/^webprofiler$/',
      'status' => FindingStatus::CRITICAL,
      'key' => $this->getPluginId() . '.webprofiler',
    ];

    $patterns[] = [
      'pattern' => '/^bad_judgement/',
      'status' => FindingStatus::CRITICAL,
      'key' => $this->getPluginId() . '.bad_judgement',
    ];

    $patterns[] = [
      'pattern' => '/^devel_generate$/',
      'status' => FindingStatus::CRITICAL,
      'key' => $this->getPluginId() . '.devel_generate',
    ];

    $patterns[] = [
      'pattern' => '/^coder$/',
      'status' => FindingStatus::CRITICAL,
      'key' => $this->getPluginId() . '.coder',
    ];

    $patterns[] = [
      'pattern' => '/.*_example$/',
      'status' => FindingStatus::ACTION_REQUESTED,
      'key' => $this->getPluginId() . '.example_modules',
    ];

    $patterns[] = [
      'pattern' => '/^devel$/',
      'status' => FindingStatus::ACTION_REQUESTED,
      'key' => $this->getPluginId() . '.devel',
    ];

    $patterns[] = [
      'pattern' => '/.*_ui/',
      'status' => FindingStatus::NEEDS_REVIEW,
      'key' => $this->getPluginId() . '.ui_modules',
    ];

    return $patterns;
  }

}
