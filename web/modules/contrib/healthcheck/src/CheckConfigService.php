<?php

namespace Drupal\healthcheck;
use Drupal\healthcheck\Entity\CheckConfig;
use Drupal\healthcheck\Plugin\HealthcheckPluginManager;

/**
 * Class CheckConfigService.
 */
class CheckConfigService implements CheckConfigServiceInterface {

  /**
   * Drupal\healthcheck\Plugin\HealthcheckPluginManager definition.
   *
   * @var \Drupal\healthcheck\Plugin\HealthcheckPluginManager
   */
  protected $checkPluginMgr;
  /**
   * Constructs a new CheckConfigService object.
   */
  public function __construct(HealthcheckPluginManager $plugin_manager_healthcheck_plugin) {
    $this->checkPluginMgr = $plugin_manager_healthcheck_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function sync() {
    $this->clean();
    $this->createDefaults();
  }

  /**
   * {@inheritdoc}
   */
  public function clean() {
    // Get all the check config objects.
    $check_configs = CheckConfig::loadMultiple();

    // Get all the check plugins.
    $checks = $this->checkPluginMgr->getDefinitions();

    // Remove every item from our list of check configs that has a plugin.
    foreach ($checks as $id => $definition) {
      unset($check_configs[$id]);
    }

    // Delete any check config entities that are left over.
    /** @var \Drupal\healthcheck\Entity\CheckConfigInterface $check_config */
    foreach ($check_configs as $check_config) {
      $check_config->delete();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDefaults() {
    // Get all the definitions from the plugin manager.
    $checks = $this->checkPluginMgr->getDefinitions();

    foreach ($checks as $id => $definition) {
      // Try to load the check config.
      $config = CheckConfig::load($id);

      // If not found...
      if (empty($config)) {
        // ..create a new plugin instance...
        $check = $this->checkPluginMgr->createInstance($id, []);

        // ..and the entity to go with it.
        $config = CheckConfig::create([
          'id' => $id,
          'label' => $definition['label'],
          'plugin_config' => $check->getConfiguration(),
        ]);

        $config->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getByTags($tags = [], $omit = []) {
    $checkdefs = $this->checkPluginMgr->getDefinitionsByTags($tags, $omit);
    $check_ids = array_keys($checkdefs);

    return CheckConfig::loadMultiple($check_ids);
  }

}
