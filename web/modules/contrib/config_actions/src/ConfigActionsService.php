<?php

namespace Drupal\config_actions;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Component\Utility\SortArray;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Base class for config_actions plugins.
 */
class ConfigActionsService implements ConfigActionsServiceInterface {
  use StringTranslationTrait;

  /**
   *  The key for locating actions in the config_actions.yml file.
   */
  const ACTION_KEY = 'actions';

  /**
   *  The internal option used to list instead of execute actions.
   */
  const CONFIG_ACTIONS_LIST_COMMAND = '_list';

  /**
   * The Action plugin manager.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $pluginManager;

  /**
   * The Source plugin manager.
   *
   * @var \Drupal\config_actions\ConfigActionsSourceManager
   */
  protected $sourceManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Collection of cached source plugins.
   *
   * @var array
   *   keyed by $this->getSourceKey($source, $type).
   */
  protected $sourceCache = [];

  /**
   * autoExecute property.
   * If TRUE, actions with "auto: false" are skipped
   * If FALSE, all actions are executed.
   * @var bool
   */
  protected $auto = FALSE;

  /**
   * merge property.
   * If TRUE, merge the destination data.
   *
   * @var bool
   */
  protected $merge = FALSE;

  /**
   * Constructs a new ConfigActionsService object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $plugin_manager
   *   The config_actions plugin manager.
   * @param \Drupal\config_actions\ConfigActionsSourceManager $source_manager
   *   The ConfigActionsSourceManager from the container.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(PluginManagerInterface $plugin_manager, ConfigActionsSourceManager $source_manager, ModuleHandlerInterface $module_handler) {
    $this->pluginManager = $plugin_manager;
    $this->sourceManager = $source_manager;
    $this->moduleHandler = $module_handler;
  }

  /**
   * Return a list of action files for the specified module
   * @param string $module_name
   * @return array of config action files
   *   'module': name of module
   *   'file': simple name of action file
   *   'path': full path to action file
   */
  protected function getConfigActionsFiles($module_name = '') {
    $result = [];
    $modules = empty($module_name)
      ? $modules = $this->moduleHandler->getModuleList()
      : [$module_name => $this->moduleHandler->getModule($module_name)];
    foreach ($modules as $module_name => $module) {
      $module_path = $module->getPath();
      // Check to see if module has a config/actions folder.
      $path = $module_path . '/' . static::CONFIG_ACTIONS_CONFIG_DIR;
      if (is_dir($path)) {
        $action_storage = new FileStorage($path, StorageInterface::DEFAULT_COLLECTION);
        $files = $action_storage->listAll();
        foreach ($files as $file) {
          $result[] = [
            'module' => $module_name,
            'file' => $file,
            'path' => $path . '/' . $file . '.yml',
          ];
        }
      }
    }
    return $result;
  }

  /**
   * Read a config_actions file and return the decoded yml data
   * @param string $filename
   * @return array
   */
  protected function readActions($filename) {
    if (file_exists($filename)) {
      return Yaml::decode(file_get_contents($filename));
    }
    return [];
  }

  /**
   * Return the cache key for the given source and type.
   * @param $source
   * @param string $type
   * @param string $base
   * @return string
   */
  protected function getSourceKey($source, $type = '', $base = '') {
    $source = is_string($source) ? $source : md5(serialize($source));
    return $base . ':' . $type . ':' . $source;
  }

  /**
   * Return an instance of a source plugin.
   * @param mixed $source
   * @param string $type
   *   source plugin id
   * @param string $base
   *   optional base namespace
   */
  protected function getSourcePlugin($source, $type = '', $base = '') {
    if (is_string($source) && (strpos($source, '::') > 0)) {
      list($type, $source) = explode('::', $source);
    }
    // Return any cached Source
    $source_key = $this->getSourceKey($source, $type, $base);
    if (isset($this->sourceCache[$source_key])) {
      return $this->sourceCache[$source_key];
    }

    // Create a new Source plugin
    /** @var \Drupal\config_actions\ConfigActionsSourceInterface $source_plugin */
    $source_plugin = NULL;
    $options = array(
      'source' => $source,
      'base' => $base,
    );

    $definitions = $this->sourceManager->getDefinitions();
    if (!empty($type) && isset($definitions[$type])) {
      // First check if we want a specific type of plugin
      $source_plugin = $this->sourceManager->createInstance($type, $options);
    }
    else {
      // Otherwise, run the auto detection on all plugins till we match.
      uasort($definitions, array(SortArray::class, 'sortByWeightElement'));
      foreach ($definitions as $plugin_id => $definition) {
        /** @var \Drupal\config_actions\ConfigActionsSourceInterface $plugin */
        $plugin = $this->sourceManager->createInstance($plugin_id, $options);
        if ($plugin->detect($source)) {
          $source_plugin = $plugin;
          break;
        }
      }
    }

    $this->sourceCache[$source_key] = $source_plugin;
    return $source_plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSource($source, $type = '', $base = '') {
    $plugin = $this->getSourcePlugin($source, $type, $base);
    $data = !empty($plugin) ? $plugin->load() : [];
    $this->merge = !empty($plugin) ? $plugin->getMerge() : FALSE;
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function saveSource($data, $dest, $type = '', $base = '', $force = FALSE) {
    $result = FALSE;
    if (!empty($dest)) {
      $plugin = $this->getSourcePlugin($dest, $type, $base);
      if (!empty($plugin)) {
        // Check if source data required merging.
        $plugin->setMerge($plugin->getMerge() || $this->merge);
        if ($force) {
          $result = $plugin->save($data);
        }
        else {
          $plugin->setData($data);
          $result = TRUE;
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function saveAll() {
    foreach ($this->sourceCache as $key => $plugin) {
      if (isset($plugin) && $plugin->isChanged()) {
        $plugin->save($plugin->getData());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clearSourceCache() {
    $this->saveAll();
    $this->sourceCache = [];
  }

  /**
   * Recursive helper function to process an action
   *
   * @param array $action
   *   array of action data
   * @param array $options
   *   optional array of action options
   *   If $options is empty, values are taken from $action data
   * @param array $overrides
   *   A set of options that always override any actions or sub-actions.
   * @param string $action_id
   *   The id string of the action to be executed.  If omitted, execute all
   *   actions in the $action array. Nested actions can be separated with a
   *   colon, such as "action:subaction"
   * @param string $action_path
   *   Recursive current full action path string being examined
   * @param array $action_list
   *   Optional array used to return a list of actions keyed by their path.
   *   If specified, the actions are NOT executed, then are just listed.
   *   Sub-actions are keyed by parent:child
   * @return mixed
   *   Returns the data that was processed, or NULL if nothing was processed
   */
  protected function doProcessAction(array $action, array $options = [], $overrides = [], $action_id = '', $action_path = '', &$action_list = NULL) {
    // Allow values in the action to override the passed options.
    $result = NULL;

    // If we are in autoExecute mode, skip any actions marked with "auto:false".
    if ($this->auto && isset($action['auto']) && ($action['auto'] === FALSE) &&
      (!isset($overrides['auto']) || ($overrides['auto'] === FALSE))) {
      return $result;
    }

    $options = NestedArray::mergeDeepArray([$options, $action, $overrides], TRUE);
    // Prune actions from options to reduce memory use
    unset($options[static::ACTION_KEY]);

    // Check to see if we specified an action_id and if it matches current path
    if (empty($action_id) || ($action_id == $action_path)) {
      if (array_key_exists(static::ACTION_KEY, $action)) {
        // Process list of nested actions.
        foreach ($action[static::ACTION_KEY] as $key => $action_item) {
          $path = (empty($action_path)) ? $key : $action_path . ':' . $key;
          $options['id'] = $key;
          // Pass an empty action_id to ensure all sub-actions are executed
          $action_item = !empty($action_item) ? $action_item : [];
          $result = $this->doProcessAction($action_item, $options, [], '', $path, $action_list);
        }
      }
      elseif (isset($action_list)) {
        // Just list the action ids, don't execute action.
        $action_list[$action_path] = $action;
      }
      else {
        // Execute a specific action.

        // Use 'default' if no plugin is specified.
        $plugin_id = !empty($action['plugin']) ? $action['plugin'] :
          (!empty($options['plugin']) ? $options['plugin'] : 'default');

        // Get Plugin instance for this action.
        /** @var \Drupal\config_actions\ConfigActionsPluginInterface $plugin */
        $plugin = $this->pluginManager->createInstance($plugin_id, $options);
        if (!isset($plugin)) {
          throw new \Exception($this->t('Could not find plugin: @name.', ['@name' => $plugin_id]));
        }
        $result = $plugin->execute($action);
      }
    }
    elseif (array_key_exists(static::ACTION_KEY, $action)) {
      // No match, so recurse down into nested actions looking for a match.
      foreach ($action[static::ACTION_KEY] as $key => $action_item) {
        $path = (empty($action_path)) ? $key : $action_path . ':' . $key;
        $options['id'] = $key;
        $action_item = !empty($action_item) ? $action_item : [];
        if ($result = $this->doProcessAction($action_item, $options, $overrides, $action_id, $path, $action_list)) {
          // Stop looping when we find an action to process, unless we are listing actions
          if (!isset($action_list)) {
            break;
          }
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function processAction(array $action, array $options = [], $action_id = '') {
    $data = $this->doProcessAction($action, [], $options, $action_id, '');
    $this->saveAll();
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function listActions(array $action) {
    $action_list = [];
    $data = $this->doProcessAction($action, [], [], '', '', $action_list);
    return $action_list;
  }

  /**
   * {@inheritdoc}
   */
  public function importAction($module_name, $action_id = '', $file = '', $variables = []) {
    $result = [];
    // Remove optional .yml extension.
    $file = str_replace('.yml', '', $file);
    // Get list of all action files within the module to loop over.
    $files = $this->getConfigActionsFiles($module_name);
    foreach ($files as $action_file) {
      if (empty($file) || $file === $action_file['file']) {
        $actions = $this->readActions($action_file['path']);
        // Rebase so any includes look in the specified module.
        $actions['module'] = !empty($actions['module']) ? $actions['module'] : $action_file['module'];
        $actions['base'] = DRUPAL_ROOT . '/' . drupal_get_path('module', $actions['module']);
        // Use file key as default source.
        $actions['source'] = !empty($actions['source']) ? $actions['source'] : $action_file['file'];

        // Prevent auto:false actions from running if not given a file or
        // specific action id.
        $this->autoExecute(empty($file) && empty($action_id));
        $result[$action_file['file']] = $this->processAction($actions, $variables, $action_id);
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($module_name = '', $file = '') {
    $result = [];
    $modules = !empty($module_name) ? [$module_name => []] : $this->moduleHandler->getModuleList();
    foreach ($modules as $module_name => $extension) {
      $files = $this->getConfigActionsFiles($module_name);
      foreach ($files as $action_file) {
        if (empty($file) || $file === $action_file['file']) {
          $actions = $this->readActions($action_file['path']);
          $result[$module_name][$action_file['file']] = $this->listActions($actions);
        }
      }
    }
    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function autoExecute($value = NULL) {
    if (isset($value)) {
      $this->auto = $value;
    }
    return $this->auto;
  }

}
