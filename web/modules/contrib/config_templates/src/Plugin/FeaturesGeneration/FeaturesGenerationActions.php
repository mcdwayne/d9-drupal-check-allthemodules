<?php

namespace Drupal\config_templates\Plugin\FeaturesGeneration;

use Drupal\config_actions\ConfigActionsTransform;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Component\Serialization\Yaml;
use Drupal\config_actions\ConfigActionsServiceInterface;
use Drupal\config_actions\Plugin\ConfigActionsSource\ConfigActionsFile;
use Drupal\config_templates\ConfigTemplatesDiffer;
use Drupal\features\Plugin\FeaturesGeneration\FeaturesGenerationWrite;
use Drupal\features\FeaturesBundleInterface;
use Drupal\features\Package;
use Drupal\features\FeaturesConfigDependencyManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class for writing packages to the local file system.
 *
 * @Plugin(
 *   id = \Drupal\config_templates\Plugin\FeaturesGeneration\FeaturesGenerationActions::METHOD_ID,
 *   weight = 4,
 *   name = @Translation("Write as Actions"),
 *   description = @Translation("Write packages as Config Action files."),
 * )
 */
class FeaturesGenerationActions extends FeaturesGenerationWrite implements ContainerFactoryPluginInterface {

  /**
   * The package generation method id.
   */
  const METHOD_ID = 'actions';

  /**
   * The name of the generated action file.
   */
  const IMPORT_ACTION_NAME = 'import';

  /**
   * The name of the generated action file that removes config.
   */
  const REMOVE_ACTION_NAME = 'delete';

  /**
   *  The action key for locating and generating sub-actions.
   */
  const SUBACTION_KEY = 'actions';

  /**
   *  The internal key used to store variables.
   */
  const VARS_KEY = '_vars';

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The configuration storage.
   *
   * @var \Drupal\Core\Config\ExtensionInstallStorage
   */
  protected $templateStorage;

  /**
   * The internal list of files to be generated.
   *
   * @var array
   */
  protected $files;

  /**
   * The action data to be generated
   *
   * @var array
   */
  protected $action;

  /**
   * The list of config ids to be generated.
   *
   * @var array
   */
  protected $configList;

  /**
   * List of template files without wildcards in the config storage.
   * @var array
   *   also keyed by filename (config id)
   */
  protected $templates;

  /**
   * List of template files with wildcards in the config storage.
   * @var array
   */
  protected $templatesWild;

  /**
   * Creates a new FeaturesGenerationActions instance.
   *
   * @param string $root
   *   The app root.
  +   * @param Drupal\Core\File\FileSystemInterface $fileSystem
  +   *   Public function __construct fileSystem.
  +   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
  +   *   Public function __construct config_factory.
  +   * @param Drupal\Core\Config\StorageInterface $config_storage
  +   *   Public function __construct config_storage.
   */
  public function __construct($root, FileSystemInterface $fileSystem, ConfigFactoryInterface $config_factory, StorageInterface $config_storage) {
    $this->configFactory = $config_factory;
    $this->templateStorage = new ExtensionInstallStorage($config_storage, ConfigActionsFile::CONFIG_TEMPLATE_DIRECTORY);
    parent::__construct($root, $fileSystem);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('app.root'),
      $container->get('file_system'),
      $container->get('config.factory'),
      $container->get('config.storage')
    );
  }

  /**
   * Creates a high performant version of the ConfigDependencyManager.
   *
   * @param array $config_ids
   *   List of configuration ids to fetch.
   * @return \Drupal\features\FeaturesConfigDependencyManager
   *   A high performant version of the ConfigDependencyManager.
   *
   * @see \Drupal\Core\Config\Entity\ConfigDependencyManager
   */
  protected function getFeaturesConfigDependencyManager(array $config_ids) {
    $dependency_manager = new FeaturesConfigDependencyManager();
    // Read all configuration using the factory. This ensures that multiple
    // deletes during the same request benefit from the static cache. Using the
    // factory also ensures configuration entity dependency discovery has no
    // dependencies on the config entity classes. Assume data with UUID is a
    // config entity. Only configuration entities can be depended on so we can
    // ignore everything else.
    $data = array_map(function (ImmutableConfig $config) {
      $data = $config->get();
      if (isset($data['uuid'])) {
        return $data;
      }
      return FALSE;
    }, $this->configFactory->loadMultiple($config_ids));
    $dependency_manager->setData(array_filter($data));
    return $dependency_manager;
  }

  /**
   * Return the destination subdirectory for the config files.
   *
   * @return string
   */
  protected function getDestDirectory() {
    return ConfigActionsFile::CONFIG_TEMPLATE_DIRECTORY;
  }

  /**
   * Process the *.info.yml file as needed.
   *
   * @param array $file
   *   Info File array to be processed.
   * @param array $config_list
   *   Optional list of config to be exported.
   *
   * @result array
   *   Processed File array.
   */
  protected function processInfo(array $file, array $config_list = []) {
    $info = Yaml::decode($file['string']);
    // Add Config Actions as dependency.
    $info['dependencies'][] = 'config_actions';
    // Resort and remove duplicates.
    $info['dependencies'] = array_unique($info['dependencies']);
    sort($info['dependencies']);
    $file['string'] = Yaml::encode($info);
    return $file;
  }

  /**
   * Process the *.features.yml file as needed.
   *
   * @param array $file
   *   Info File array to be processed.
   * @param array $config_list
   *   Optional list of config to be exported.
   * @result array|NULL
   *   Processed File array.
   */
  protected function processFeature(array $file, array $config_list = []) {
    // Delete features.yml file for now until Features can be updated to
    // handle config specifically listed in this file.
    return NULL;

    // Remove features.yml file since this isn't a Feature.
    // Explicitly set the list of config contained in this export.
    $info = Yaml::decode($file['string']);
    //TODO: Add 'include' to features to indicate which config is exported.
    $info['include'] = $config_list;
    $file['string'] = Yaml::encode($info);
    return $file;
  }

  /**
   * Initialize the empty import action data.
   *
   * @return array
   *   Action data
   */
  protected function initImportAction() {
    return [
      'source' => ['@id@', '@id@.yml'],
      'dest' => '@id@',
      'actions' => [],
    ];
  }

  /**
   * Initialize the empty remove action data.
   *
   * @return array
   *   Action data
   */
  protected function initRemoveAction() {
    return [
      'source' => '@id@',
      'plugin' => 'delete',
      'auto' => false,
      'actions' => [],
    ];
  }

  /**
   * Process the config for the given $id and return any action data.
   *
   * @param string $id
   *   Config id.
   * @param string $directory
   *   Config directory for module.
   * @param array $file file data
   *   Set this to NULL to prevent the original file from being generated.
   *
   * @return array
   *   Action value data.
   */
  protected function generateAction($id, $directory, array &$file) {
    // Update subdirectory location
    $file['subdirectory'] = $this->getDestDirectory();

    $action = [];
    $config = Yaml::decode($file['string']);

    $template = [];
    // First, look for exact matching template.
    if (isset($this->templates[$id])) {
      // Read the existing template.
      $template = $this->templateStorage->read($id);
    }
    // Next, look for a wildcard match.
    else {
      foreach ($this->templatesWild as $pattern) {
        $vars = ConfigActionsTransform::parseWildcards($pattern, $id, $config);
        if (!empty($vars)) {
          $template = $this->templateStorage->read($pattern);
          $template = ConfigActionsTransform::replaceTree($template,
            array_keys($vars), array_values($vars));
          $action['template'] = $pattern;
          $action[self::VARS_KEY] = $vars;
          break;
        }
      }
    }

    if (empty($template)) {
      // Check for template already existing in this module.
      // Since module is likely uninstalled, it won't be in the templates list.
      $filepath = $this->root . '/' . $directory . '/' . $file['subdirectory'];
      if (file_exists($filepath . '/' . $file['filename'])) {
        // Read the existing template
        $template_storage = new FileStorage($filepath, StorageInterface::DEFAULT_COLLECTION);
        $template = $template_storage->read($id);
      }
    }

    // Compute overrides from existing template.
    if (!empty($template)) {
      /** @var \Drupal\config_templates\ConfigTemplatesDiffer $config_diff */
      $config_diff = new ConfigTemplatesDiffer(\Drupal::service('string_translation'));
      $diff = $config_diff->diff($template, $config);

      foreach ($diff->getEdits() as $edit) {
        if (in_array($edit->type, ['change', 'add'])) {
          foreach ($edit->closing as $item) {
            $items = explode(' : ', $item);
            $value = (count($items) == 2) ? $items[1] : '';
            // Convert special NULL values correctly.
            $value = $config_diff->decodeNullValue($value);
            $keys = explode('::', $items[0]);
            // Don't override dependencies.  Core config will handle those.
            if (!empty($keys) && $keys[0] !== 'dependencies') {
              $value_path = &$action[$edit->type];
              foreach ($keys as $key) {
                if (!isset($value_path[$key])) {
                  $value_path[$key] = [];
                }
                $value_path = &$value_path[$key];
              }
              $value_path = $value;
            }
          }
        }
      }
      // Remove file so current template is not overwritten.
      $file = NULL;
    }
    if (empty($action)) {
      // Ensure action has some value so user knows where to add overrides.
      $action['change'] = [];
    }

    return $action;
  }

  /**
   * Load list of templates found on site and cache.
   */
  protected function initTemplates() {
    $this->templates = $this->templateStorage->listAll();

    // Split out templates with wildcard names.
    $this->templatesWild = preg_grep('/\@[a-zA-Z0-9_\-]+\@/', $this->templates);
    $this->templates = array_diff($this->templates, $this->templatesWild);
    // Key the template array for faster lookup.
    $this->templates = array_combine($this->templates, $this->templates);
  }

  /**
   * Encode the Action as string data for file writing.
   *
   * @param $action
   *   Action array
   *
   * @return string
   */
  protected function encodeAction($action) {
    // Could just call Yaml::encode, but we want a blank line between each
    // sub-action for better readability.
    $sub_actions = isset($action[self::SUBACTION_KEY]) ? $action[self::SUBACTION_KEY] : [];
    unset($action[self::SUBACTION_KEY]);
    $output = Yaml::encode($action) . "\n" . self::SUBACTION_KEY . ":\n";
    foreach ($sub_actions as $id => $sub_action) {
      // Add each sub-action with blank lines and proper indentation.
      $output .= "\n  " . $id . ":\n    " . str_replace("\n", "\n    ", Yaml::encode($sub_action));
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   */
  protected function preparePackage(Package $package, array $existing_packages, FeaturesBundleInterface $bundle = NULL) {
    // Temporarily change root path to prevent parent from deleting config/install folders.
    $root = $this->root;
    $this->root = '/_null';  // A root path that does not exist.
    parent::preparePackage($package, $existing_packages, $bundle);
    // Restore root path.
    $this->root = $root;

    // Prepare the Config Action file.

    // List of directories containing config.
    $directories = [
      InstallStorage::CONFIG_INSTALL_DIRECTORY,
      InstallStorage::CONFIG_OPTIONAL_DIRECTORY
    ];
    $this->initTemplates();

    // Collect all the config ids being exported.
    $this->configList = [];
    $file_list = [];
    $this->files = $package->getFiles();
    foreach ($this->files as $config_id => $file) {
      // Check if this is a config file being written to one of the above
      // directories.
      if (in_array($file['subdirectory'], $directories)) {
        // Add this config to the list for the actions to be reordered below.
        $this->configList[] = $config_id;
      }
      else {
        $file_list[$config_id] = $file;
      }
    }

    // Put the config ids into dependency order.
    if (!empty($this->configList)) {
      $dependency_manager = $this->getFeaturesConfigDependencyManager($this->configList);
      $this->configList = $dependency_manager->sortAll();

      // Now put the files into dependency order.
      foreach ($this->configList as $config_id) {
        $file_list[$config_id] = $this->files[$config_id];
      }

      // Create initial action data.
      $this->action = $this->initImportAction();
    }

    // Process the Info file as needed.
    $file_list['info'] = $this->processInfo($file_list['info'], $this->configList);

    // Process the Features file as needed.
    $file_list['features'] = $this->processFeature($file_list['features'], $this->configList);

    // Add an action to remove all the config created by this feature.
    $file_list[self::REMOVE_ACTION_NAME] = $this->generateRemoveActionFile($this->configList);

    // Use the new ordered list of files.
    $this->files = array_filter($file_list);

    $package->setFiles($this->files);
  }

  /**
   * Generate the file array for the action.
   *
   * @param array $action
   *   The action data to be exported.
   * @return array
   *   Action file data.
   */
  protected function generateActionFile(array $action) {
    // Loop through the sub-actions and aggregate global variables.
    if (isset($action[self::SUBACTION_KEY])) {
      $global_vars = [];
      $local_vars = [];
      foreach ($action[self::SUBACTION_KEY] as $action_id => $sub_action) {
        if (isset($sub_action[self::VARS_KEY])) {
          foreach ($sub_action[self::VARS_KEY] as $var_name => $var_value) {
            if (isset($local_vars[$var_name])) {
              // Skip variables already identified as local.
            }
            else if (!isset($global_vars[$var_name])) {
              // First time a variable is used, assume global.
              $global_vars[$var_name] = $var_value;
            }
            else if ($global_vars[$var_name] !== $var_value) {
              // Variable used, but has different value, so mark as local.
              $local_vars[$var_name] = $var_value;
            }
          }
        }
      }
      // Remove local vars from global list.
      $global_vars = array_diff_key($global_vars, $local_vars);
      // Finally, go back through sub-actions and remove global vars from local.
      foreach ($action[self::SUBACTION_KEY] as $action_id => &$sub_action) {
        if (isset($sub_action[self::VARS_KEY])) {
          $vars = array_diff_key($sub_action[self::VARS_KEY], $global_vars);
          // Remove vars array and merge local variables directly.
          unset($sub_action[self::VARS_KEY]);
          // Recreate action to get vars added near the top after template.
          $first = array_slice($sub_action, 0, 1, true);
          $last = array_slice($sub_action, 1, count($sub_action)-1);
          $sub_action = $first + $vars + $last;
        }
      }
      unset($sub_action);

      // Create a new action array to get the global variables listed before
      // the sub-actions.
      $sub_actions = $action[self::SUBACTION_KEY];
      unset($action[self::SUBACTION_KEY]);
      $action = array_merge($action, $global_vars);
      $action[self::SUBACTION_KEY] = $sub_actions;
    }

    $action_file = [
      'filename' => self::IMPORT_ACTION_NAME . '.yml',
      'subdirectory' => ConfigActionsServiceInterface::CONFIG_ACTIONS_CONFIG_DIR,
      'string' => $this->encodeAction($action),
    ];
    return $action_file;
  }

  /**
   * Generate the file array for the remove action.
   *
   * @param array $config_list
   *   List of config ids to be removed.
   * @return array
   *   Action file data.
   */
  protected function generateRemoveActionFile(array $config_list) {
    $action = $this->initRemoveAction();

    // Reverse the dependency order of the config
    $config_list = array_reverse($config_list);
    foreach ($config_list as $config_id) {
      $action[self::SUBACTION_KEY][$config_id] = [];
    }
    $action_file = [
      'filename' => self::REMOVE_ACTION_NAME . '.yml',
      'subdirectory' => ConfigActionsServiceInterface::CONFIG_ACTIONS_CONFIG_DIR,
      'string' => $this->encodeAction($action),
    ];
    return $action_file;
  }

  /**
   * {@inheritdoc}
   */
  protected function generatePackage(array &$return, Package $package) {
    parent::generatePackage($return, $package);

    // Now generate the Action file.
    if (!empty($this->action[self::SUBACTION_KEY])) {
      $action_file = $this->generateActionFile($this->action);
      $success = TRUE;
      try {
        $this->generateFile($package->getDirectory(), $action_file);
      }
      catch (\Exception $exception) {
        $this->failure($return, $package, $exception);
        $success = FALSE;
      }
      if ($success) {
        $this->success($return, $package);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function generateFile($directory, array $file) {
    $id = str_replace('.yml', '', $file['filename']);
    if (in_array($id, $this->configList)) {
      $this->action[self::SUBACTION_KEY][$id] = $this->generateAction($id, $directory, $file);
    }
    // Only generate the file if it's still defined.
    if (!empty($file)) {
      parent::generateFile($directory, $file);
    }
  }

}
