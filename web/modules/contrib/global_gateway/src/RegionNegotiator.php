<?php

namespace Drupal\global_gateway;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class responsible for performing language negotiation.
 */
class RegionNegotiator implements RegionNegotiatorInterface {

  /**
   * The language negotiation method plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $negotiatorManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The settings instance.
   *
   * @var \Drupal\Core\Site\Settings
   */
  protected $settings;

  /**
   * The request stack object.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current active user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Local cache for language negotiation method instances.
   *
   * @var array
   */
  protected $types;

  /**
   * Disabled regions processor.
   *
   * @var \Drupal\global_gateway\DisabledRegionsProcessor
   */
  protected $disabledRegionsProcessor;

  /**
   * Constructs a new LanguageNegotiator object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $negotiator_manager
   *   The language negotiation methods plugin manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Site\Settings $settings
   *   The settings instance.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\global_gateway\DisabledRegionsProcessor $disabled_processor
   *   Disabled regions processor.
   */
  public function __construct(
    PluginManagerInterface $negotiator_manager,
    ConfigFactoryInterface $config_factory,
    Settings $settings,
    RequestStack $requestStack,
    DisabledRegionsProcessor $disabled_processor
  ) {
    $this->negotiatorManager = $negotiator_manager;
    $this->configFactory = $config_factory;
    $this->settings = $settings;
    $this->requestStack = $requestStack;
    $this->disabledRegionsProcessor = $disabled_processor;
  }

  public function getConfig() {
    return $this->configFactory->get('global_gateway.negotiator')->get('types');
  }

  public function getEnabledNegotiators() {
    $collection = $config = $definitions = [];

    $configs = $this->getConfig();
    $definitions = $this->negotiatorManager->getDefinitions();

    if (!empty($configs) && !empty($definitions)) {
      $config = array_intersect_key($configs, $definitions);
    }
    else {
      $config = [];
    }

    if (!empty($config)) {
      $collection = new RegionNegotiationPluginCollection(
        $this->negotiatorManager,
        $config
      );
      $collection = $collection->sort();
    }

    foreach ($collection as $negotiator) {
      if (!empty($negotiator->getConfiguration()['enabled'])) {
        yield $negotiator;
      }
    }

    yield [];
  }

  public function getNegotiators() {
    $types = $this->getConfig();

    foreach (array_keys($this->negotiatorManager->getDefinitions()) as $id) {
      $config = !empty($types[$id]) ? $types[$id] : [];

      yield $this->negotiatorManager
        ->getInstance(['id' => $id, 'config' => $config]);
    }
  }

  public function getNegotiator($id) {
    $types = $this->getConfig();

    $config = !empty($types[$id]) ? $types[$id] : [];

    return $this->negotiatorManager
      ->getInstance(['id' => $id, 'config' => $config]);
  }

  public function negotiateRegion() {
    $region_code = NULL;

    foreach ($this->getEnabledNegotiators() as $negotiator) {
      if ($negotiator instanceof RegionNegotiationTypeInterface) {
        $region_code = $negotiator->getRegionCode($this->requestStack->getCurrentRequest());
      }

      if (!is_null($region_code) && $this->disabledRegionsProcessor->isDisabled($region_code)) {
        $region_code = $this->disabledRegionsProcessor->getFallbackRegionCode($region_code);
      }

      if (!empty($region_code)) {
        break;
      }
    }
    return $region_code;
  }

  /**
   * {@inheritdoc}
   */
  public function saveConfiguration($types = []) {
    $config = $this->configFactory->getEditable('global_gateway.negotiator');
    $settings = $config->get('types');

    if (empty($settings) || !is_array($settings)) {
      $settings = [];
    }

    if (!empty($settings)) {
      foreach (array_keys($types) as $type_id) {
        if (!isset($settings[$type_id])) {
          $types[$type_id]['plugin'] = $type_id;
          $types[$type_id]['id'] = $type_id;
          $settings[$type_id] = $types[$type_id];
        }
        else {
          $settings[$type_id] = array_replace($settings[$type_id], $types[$type_id]);
        }
      }
    }
    else {
      foreach ($types as $id => $type) {
        $type['plugin'] = $id;
        $settings[$id] = $type;
      }
    }

    $plugins = new RegionNegotiationPluginCollection($this->negotiatorManager, $settings);

    $config->set('types', $plugins->getConfiguration());
    $config->save();
  }

}
