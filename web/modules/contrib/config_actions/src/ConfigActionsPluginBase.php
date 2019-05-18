<?php

namespace Drupal\config_actions;

use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for config_actions plugins.
 */
class ConfigActionsPluginBase extends PluginBase implements ConfigActionsPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The list of allowed option keys.
   * Taken from the plugin annotation.
   * @var array
   *   key/value array where the key is the option name and the value is the default.
   */
  protected $allowedOptions = [];

  /**
   * Optional data used by the plugins.
   * Taken from the plugin annotation.
   * @var array
   */
  protected $pluginData = [];

  /**
   * @var \Drupal\config_actions\ConfigActionsServiceInterface
   */
  protected $actionService;

  /**
   * The id name of the plugin instance.
   * @var string
   */
  protected $pluginId;

  /**
   * The id of the action.
   * @var string
   */
  protected $id;

  /**
   * The pattern of the action key (id).
   * @var string
   */
  protected $key;

  /**
   * Source data to manipulate.
   * @var mixed
   */
  protected $source;

  /**
   * Optional destination id of config item.
   * @var string
   */
  protected $dest;

  /**
   * Plugin type of the source.
   * @var string
   */
  protected $source_type;

  /**
   * Plugin type of the destination.
   * @var string
   */
  protected $dest_type;

  /**
   * Optional Base path for source and dest.
   * @var string
   */
  protected $base;

  /**
   * Optional Module name for source templates.
   * @var string
   */
  protected $module;

  /**
   * Overrides the Source with an existing config/templates template defined
   * in some installed module.
   * @var string
   */
  protected $template;

  /**
   * If FALSE, do not run action when service has autoExecute enabled.
   * @var bool
   */
  protected $auto;

  /**
   * List of string replacement variables and values.
   * @var array
   */
  protected $replace;

  /**
   * List of options that allow string replacement.
   * @var array
   */
  protected $replace_in = [];

  /**
   * List to keep track of what options have had string replacement.
   * @var array
   */
  protected $did_replace = [];

  /**
   * Optional config corresponding to id.
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * Constructs a new ConfigActionsPlugin object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param ConfigActionsServiceInterface $config_action_service
   *   The ConfigActionsService from the container.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,
      ConfigActionsServiceInterface $config_action_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->actionService = $config_action_service;
    $this->pluginId = $plugin_id;
    $this->allowedOptions = $plugin_definition['options'];
    $this->replace_in = $plugin_definition['replace_in'];
    $this->pluginData = $plugin_definition['data'];
    $this->addAllowed([
      'id' => '',
      'key' => '',
      'source' => '',
      'source_type' => '',
      'dest' => NULL,
      'dest_type' => '',
      'replace' => [],
      'replace_in' => [],
      'base' => '',
      'module' => '',
      'auto' => TRUE,
      'template' => '',
    ]);
    $this->initPlugin($configuration);
  }

  /**
   * Create a plugin instance from the container
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var ConfigActionsServiceInterface $config_action_service */
    $config_action_service = $container->get('config_actions');
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $config_action_service
    );
  }

  /**
   * Initialize a plugin.
   *
   * This is the place a plugin can add options to allowedOptions or perform
   * any other needed initialization, such as traits.
   * @param array $configuration
   *   Contains the data for the plugin action.
   */
  protected function initPlugin(array $configuration) {
    $this->setOptions($configuration);
  }

  /**
   * Add additional allowed options.
   * Used by Traits in plugins to add global options.
   * @param array $allowed
   *   key/value array where key is option name and value is default
   */
  protected function addAllowed(array $allowed) {
    $this->allowedOptions = array_merge($this->allowedOptions, $allowed);
  }

  /**
   * Process an Options array to set various internal variable defaults.
   *
   * @param array $options
   */
  public function setOptions(array $options) {
    $default_list = [];

    // Loop through $options to look for simple @var@ definitions that
    // belong in the $replace list.
    foreach ($options as $key => $value) {
      if (preg_match('/^\@[A-Za-z0-9_\-]+\@$/', $key) === 1) {
        // Only set simple @var@ if not already defined in replacements.
        if (!isset($options['replace'][$key])) {
          $options['replace'][$key] = $value;
        }
        unset($options[$key]);
      }
    }

    // Load any supplied and allowed options into class properties.
    foreach ($this->allowedOptions as $key => $default) {
      if (array_key_exists($key, $options)) {
        $this->{$key} = $options[$key];
      }
      elseif (property_exists($this, $key) && is_null($this->{$key})) {
        $this->{$key} = $default;
        $default_list[] = $key;
      }
    }

    $parsed_options = $this->parseOptions($options, ['source', 'dest']);

    // Extract any variables from the key/id
    if (!empty($this->key)) {
      $this->replace = array_merge(ConfigActionsTransform::parseWildcards($this->key, $this->id), $this->replace);
    }

    if (!empty($this->replace_in)) {
      foreach ($this->replace_in as $option) {
        if (isset($parsed_options[$option])) {
          $this->{$option} = $this->replaceData($parsed_options[$option], $this->replace, $option);
        }
      }
    }

    if (!isset($this->dest) && !is_array($this->source)) {
      $this->dest = $this->source;
      $this->dest_type = $this->source_type;
    }
  }

  /**
   * Return a current option value.
   * @param string $name
   * @return mixed
   */
  public function getOption($name) {
    return isset($this->{$name}) ? $this->{$name} : NULL;
  }

  /**
   * Return True if array is Sequential
   * @param array $arr
   * @return bool
   */
  protected function isSequential($arr)
  {
    if (array() === $arr) return false;
    return is_array($arr) && (array_keys($arr) == range(0, count($arr) - 1));
  }

  /**
   * Parse any property references in the options.
   * @param array $options
   * @oaram array $setKeys
   *   a list of property keys to be set if they are simple strings
   * @result array of processed options
   */
  public function parseOptions(array $options, array $setKeys = []) {
    $result = [];

    if (!empty($this->module)) {
      $this->base = DRUPAL_ROOT . '/' . drupal_get_path('module', $this->module);
    }

    // Perform any property substitution in the loaded defaults.
    $replacements = [];
    foreach ($this->allowedOptions as $key => $default) {
      if (isset($this->{$key}) && is_string($this->{$key})) {
        $replacements["@$key@"] = $this->{$key};
      }
    }
    foreach ($this->allowedOptions as $key => $default) {
      if (isset($this->{$key})) {
        $result[$key] = $this->replaceData($this->{$key}, $replacements);
        // Check if this is a string property, or a simple sequential array
        // we want to save directly
        if ((is_string($this->{$key}) || $this->isSequential($this->{$key})) && in_array($key, $setKeys)) {
          $this->{$key} = $result[$key];
        }
      }
    }

    // Update any replacement values with property variables like @id@
    if (!empty($this->replace)) {
      foreach ($this->replace as $key => $value) {
        if (isset($value) && !empty($replacements)) {
          $this->replace[$key] = ConfigActionsTransform::replace($value, $replacements);
        }
      }
    }

    return $result;
  }

  /**
   * Return a specific property from the plugin specific data.
   * @param $property
   * @return mixed
   */
  protected function getData($property) {
    if (array_key_exists($property, $this->pluginData)) {
      return $this->pluginData[$property];
    }
  }

  /**
   * Perform string replacement on the $data and return the result.
   * @param mixed $data
   * @param array $replacements
   *   If specified, overrides the stored replacement string list
   * @param string $in
   *   Name of "replace_in" option to restrict replacements
   * @result mixed
   */
  protected function replaceData($data, $replacements = NULL, $in = '') {
    $replacements = isset($replacements) ? $replacements :
      (!empty($this->replace) ? $this->replace : []);
    $replace = [];
    $replace_keys = [];
    foreach ($replacements as $pattern => $value) {
      if (is_array($value)) {
        $replace_in = isset($value['in']) ? $value['in'] : $this->replace_in;
        $pattern = isset($value['pattern']) ? $value['pattern'] : $pattern;
        if (empty($in) || in_array($in, $replace_in)) {
          $with = isset($value['with']) ? $value['with'] : '';
          $type = isset($value['type']) ? $value['type'] : 'value';
          if (is_string($type)) {
            $type = explode(',', $type);
          }
          if (in_array('key', $type)) {
            $replace_keys[$pattern] = $with;
          }
          if (in_array('value', $type)) {
            $replace[$pattern] = $with;
          }
        }
      }
      else {
        $replace[$pattern] = $value;
        $replace_keys[$pattern] = $value;
      }
    }
    return ConfigActionsTransform::replace($data, $replace, $replace_keys);
  }

  /**
   * {@inheritdoc}
   */
  public function transform(array $source) {
    return $source;
  }

  /**
   * {@inheritdoc}
   */
  public function execute(array $action) {
    // Load any specified config.
    if (!empty($this->template)) {
      // Grab additional variables from template name wildcards.
      $this->replace = array_merge($this->replace, ConfigActionsTransform::parseWildcards($this->template, $this->dest));
      // Override the source plugin type.
      if (is_array($this->source)) {
        $this->source[] = 'template::' . $this->template;
      }
      else {
        $this->source = $this->template;
        $this->source_type = 'template';
      }
    }
    $tree = $this->actionService->loadSource($this->source, $this->source_type, $this->base);
    if (!is_array($tree)) {
      $tree = [];
    }

    if (in_array('load', $this->replace_in)) {
      $tree = $this->replaceData($tree, $this->replace, 'load');
    }

    // Transform the source data and return new data.
    $tree = $this->transform($tree);

    if (in_array('save', $this->replace_in)) {
      $tree = $this->replaceData($tree, $this->replace, 'save');
    }

    // Save new data tree to destination.
    $this->actionService->saveSource($tree, $this->dest, $this->dest_type, $this->base, is_array($this->source));

    return $tree;
  }

}
