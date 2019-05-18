<?php

namespace Drupal\plus\Plugin\Theme;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plus\AlterManager;
use Drupal\plus\Events\ThemeEvent;
use Drupal\plus\Plugin\PluginBase;
use Drupal\plus\Plus;
use Drupal\plus\ProviderManagerProvider;
use Drupal\plus\SettingManagerProvider;
use Drupal\plus\ThemeSettings;
use Drupal\plus\UpdateManagerProvider;
use Drupal\plus\Utility\Crypt;
use Drupal\plus\Utility\Storage;
use Drupal\plus\Utility\StorageItem;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Theme\ThemeManagerInterface;
use Drupal\Core\Url;
use Drupal\plus\Utility\Unicode;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base theme object.
 *
 * @Theme("_base")
 *
 * @ingroup plugins_theme
 */
class ThemeBase extends PluginBase implements ThemeInterface {

  /**
   * Ignores the following directories during file scans of a theme.
   *
   * @see \Drupal\plus\Theme::IGNORE_ASSETS
   * @see \Drupal\plus\Theme::IGNORE_CORE
   * @see \Drupal\plus\Theme::IGNORE_DOCS
   * @see \Drupal\plus\Theme::IGNORE_DEV
   */
  const IGNORE_DEFAULT = -1;

  /**
   * Ignores the directories "assets", "css", "images" and "js".
   */
  const IGNORE_ASSETS = 0x1;

  /**
   * Ignores the directories "config", "lib" and "src".
   */
  const IGNORE_CORE = 0x2;

  /**
   * Ignores the directories "docs" and "documentation".
   */
  const IGNORE_DOCS = 0x4;

  /**
   * Ignores "bower_components", "grunt", "node_modules" and "starterkits".
   */
  const IGNORE_DEV = 0x8;

  /**
   * Ignores the directories "templates" and "theme".
   */
  const IGNORE_TEMPLATES = 0x16;

  /**
   * @var \Drupal\plus\Core\Extension\AlterManager
   */
  protected $alterManager;

  /**
   * @var \Drupal\plus\FormAlterPluginManager
   */
  protected $alterFormManager;

  /**
   * Flag indicating if the theme is in "development" mode.
   *
   * This property can only be set via `settings.local.php`:
   *
   * @code
   * $settings['theme.dev'] = TRUE;
   * @endcode
   *
   * @var bool
   */
  protected $dev;

  /**
   * The current theme Extension object.
   *
   * @var \Drupal\Core\Extension\Extension
   */
  protected $extension;

  /**
   * Flag indicating whether theme has been initialized.
   *
   * @var bool
   */
  protected $initialized = FALSE;

  /**
   * A URL for where a livereload instance is listening, if set.
   *
   * This property can only be set via `settings.local.php`:
   *
   * @code
   * // Enable default value: //127.0.0.1:35729/livereload.js.
   * $settings['theme.livereload'] = TRUE;
   *
   * // Or, set just the port number: //127.0.0.1:12345/livereload.js.
   * $settings['theme.livereload'] = 12345;
   *
   * // Or, Set an explicit URL.
   * $settings['theme.livereload'] = '//127.0.0.1:35729/livereload.js';
   * @endcode
   *
   * @var string
   */
  protected $livereload;

  /**
   * @var \Drupal\plus\ProviderManagerProvider
   */
  protected $providerManager;

  /**
   * @var \Drupal\plus\SettingManagerProvider
   */
  protected $settingManager;

  /**
   * @var \Drupal\plus\Plugin\Theme\Template\ProviderPluginManager
   */
  protected $templateManager;

  /**
   * Theme handler object.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * @var \Drupal\Core\Theme\ThemeManagerInterface
   */
  protected $themeManager;

  /**
   * The update plugin manager.
   *
   * @var \Drupal\plus\UpdateManagerProvider
   */
  protected $updateManager;

  /**
   * @var array
   */
  protected $preprocessVariables;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ThemeHandlerInterface $theme_handler, ThemeManagerInterface $theme_manager) {
    if ($plugin_id === '_base') {
      $plugin_id = $configuration['theme'];
      unset($configuration['theme']);
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->extension = $theme_handler->getTheme($plugin_id);
    $this->themeHandler = $theme_handler;
    $this->themeManager = $theme_manager;
  }

  /**
   * Magic function to reduce object to the theme's machine name.
   *
   * @return string
   *   Theme machine name.
   */
  public function __toString() {
    return $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function addCallback(array &$callbacks, $callback, $replace = NULL, $placement = 'append') {
    return Plus::addCallback($callbacks, $callback, $replace, $placement);
  }

  /**
   * {@inheritdoc}
   */
  public function autoloadFixInclude() {
    return drupal_get_path('module', 'plus') . '/autoload-fix.php';
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('theme_handler'),
      $container->get('theme.manager')
    );

    $instance->setContainer($container);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultVariables() {
    return [
      // Allow modules to pass context to themes.
      // @see https://drupal.org/node/2035055
      'context' => [],

      // Allow icons to be utilized.
      // @see https://drupal.org/node/2219965
      'icon' => NULL,
      'icon_position' => 'before',
      'icon_only' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function deprecated() {
    // Log backtrace.
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    \Drupal::logger('plus')->warning('<pre><code>' . print_r($backtrace, TRUE) . '</code></pre>');

    if ($this->plus->getActiveTheme()->getSetting('suppress_deprecated_warnings', FALSE)) {
      return;
    }

    // Extrapolate caller.
    $caller = $backtrace[1];
    $class = '';
    if (isset($caller['class'])) {
      $parts = explode('\\', $caller['class']);
      $class = array_pop($parts) . '::';
    }

    if ($class) {
      drupal_set_message(t('The following method has been deprecated, please check the logs for a more detailed backtrace on where this is being invoked.'), 'warning');
    }
    else {
      drupal_set_message(t('The following procedural function has been deprecated, please check the logs for a more detailed backtrace on where this is being invoked.'), 'warning');
    }

    $url = '';
    if ($base = $this->documentationUrl(TRUE)) {
      $url = $base . Html::escape($class . $caller['function']);
    }

    if ($url) {
      drupal_set_message(t('<a href=":url" target="_blank">@title</a>', [
        ':url' => $url,
        '@title' => ($class ? $caller['class'] . $caller['type'] : '') . $caller['function'] . '()',
      ]), 'warning');
    }
    else {
      drupal_set_message(t('@title', [
        '@title' => ($class ? $caller['class'] . $caller['type'] : '') . $caller['function'] . '()',
      ]), 'warning');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function documentationUrl($search = FALSE) {
  }

  /**
   * {@inheritdoc}
   */
  public function doAlter($function, &$data, &$context1 = NULL, &$context2 = NULL) {
    // Immediately return if the theme is not Plus based.
    if (!$this->isPlus()) {
      return;
    }

    // Extract the alter hook name.
    $hook = Unicode::extractHook($function, 'alter');

    // Handle form alters as a separate plugin.
    if (strpos($hook, 'form_') === 0 && $context1 instanceof FormStateInterface) {
      $form_state = $context1;
      $form_id = $context2;

      // Due to a core bug that affects admin themes, we should not double
      // process the "system_theme_settings" form twice in the global
      // hook_form_alter() invocation.
      // @see https://drupal.org/node/943212
      if ($form_id === 'system_theme_settings') {
        return;
      }

      // Keep track of the form identifiers.
      $ids = [];

      // Get the build data.
      $build_info = $form_state->getBuildInfo();

      // Extract the base_form_id.
      $base_form_id = !empty($build_info['base_form_id']) ? $build_info['base_form_id'] : FALSE;
      if ($base_form_id) {
        $ids[] = $base_form_id;
      }

      // If there was no provided form identifier, extract it.
      if (!$form_id) {
        $form_id = !empty($build_info['form_id']) ? $build_info['form_id'] : Unicode::extractHook($function, 'alter', 'form');
      }
      if ($form_id) {
        $ids[] = $form_id;
      }

      // Iterate over each form identifier and look for a possible plugin.
      foreach ($ids as $id) {
        /** @var \Drupal\plus\Core\Form\FormAlterInterface $form */
        if ($this->alterFormManager->hasDefinition($id) && ($form = $this->alterFormManager->createInstance($id, ['theme' => $this]))) {
          $data['#submit'][] = [get_class($form), 'submitForm'];
          $data['#formValidate'][] = [get_class($form), 'validateForm'];
          $form->alter($data, $form_state, $form_id);
        }
      }
    }
    // Process hook alter normally.
    else {
      /** @var \Drupal\plus\Plugin\Alter\AlterInterface $instance */
      if ($this->alterManager->hasDefinition($hook) && ($instance = $this->alterManager->createInstance($hook, ['theme' => $this]))) {
        $instance->alter($data, $context1, $context2);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function drupalSettings() {
    // Immediately return if theme is not Bootstrap based.
    if (!$this->isPlus()) {
      return [];
    }

    $cache = $this->getCache('drupalSettings');
    $drupal_settings = $cache->getAll();
    if (!$drupal_settings) {
      foreach ($this->getSettingPlugin() as $name => $setting) {
        if ($setting->drupalSettings()) {
          $drupal_settings[$name] = TRUE;
        }
      }
      $cache->setMultiple($drupal_settings);
    }

    $drupal_settings = array_intersect_key($this->settings()->get(), $drupal_settings);

    // Indicate that theme is in dev mode.
    if ($this->isDev()) {
      $drupal_settings['dev'] = TRUE;
    }

    return $drupal_settings;
  }

  /**
   * {@inheritdoc}
   */
  public function fileScan($mask, $subdir = NULL, array $options = []) {
    $path = $this->getPath();

    // Append addition sub-directories to the path if they were provided.
    if (isset($subdir)) {
      $path .= '/' . $subdir;
    }

    // Default ignore flags.
    $options += [
      'ignore_flags' => self::IGNORE_DEFAULT,
    ];
    $flags = $options['ignore_flags'];
    if ($flags === self::IGNORE_DEFAULT) {
      $flags = self::IGNORE_CORE | self::IGNORE_ASSETS | self::IGNORE_DOCS | self::IGNORE_DEV;
    }

    // Save effort by skipping directories that are flagged.
    if (!isset($options['nomask']) && $flags) {
      $ignore_directories = [];
      if ($flags & self::IGNORE_ASSETS) {
        $ignore_directories += ['assets', 'css', 'images', 'js'];
      }
      if ($flags & self::IGNORE_CORE) {
        $ignore_directories += ['config', 'lib', 'src'];
      }
      if ($flags & self::IGNORE_DOCS) {
        $ignore_directories += ['docs', 'documentation'];
      }
      if ($flags & self::IGNORE_DEV) {
        $ignore_directories += ['bower_components', 'grunt', 'node_modules', 'starterkits'];
      }
      if ($flags & self::IGNORE_TEMPLATES) {
        $ignore_directories += ['templates', 'theme'];
      }
      if (!empty($ignore_directories)) {
        $options['nomask'] = '/^' . implode('|', $ignore_directories) . '$/';
      }
    }

    // Retrieve cache.
    $files = $this->getCache('files');

    // Generate a unique hash for all parameters passed as a change in any of
    // them could potentially return different results.
    $hash = Crypt::generateHash($mask, $path, $options);

    if (!$files->has($hash)) {
      $files->set($hash, file_scan_directory($path, $mask, $options));
    }
    return $files->get($hash, []);
  }

  /**
   * {@inheritdoc}
   */
  public function getAlterManager() {
    return $this->alterManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getAncestry($reverse = FALSE) {
    $name = $this->getName();
    $themes = $this->themeHandler->listInfo();
    $ancestry = $this->themeHandler->getBaseThemes($themes, $name);
    $ancestry[$name] = $themes[$name];
    return array_map(function ($name) {
      return $this->plus->getTheme($name);
    }, array_keys($reverse ? array_reverse($ancestry) : $ancestry));
  }

  /**
   * {@inheritdoc}
   */
  public function getCache($name, array $context = [], $default = []) {
    static $cache = [];

    // Prepend the theme name as the first context item, followed by cache name.
    array_unshift($context, $name);
    array_unshift($context, $this->getName());

    // Join context together with ":" and use it as the name.
    $name = implode(':', $context);

    if (!isset($cache[$name])) {
      $storage = self::getStorage();
      $value = $storage->get($name);
      if (!isset($value)) {
        $value = is_array($default) ? new StorageItem($default, $storage) : $default;
        $storage->set($name, $value);
      }
      $cache[$name] = $value;
    }

    return $cache[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementClass() {
    return 'Drupal\\plus\\Utility\\Element';
  }

  /**
   * {@inheritdoc}
   */
  public function getExtension() {
    return $this->extension;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormManager() {
    return $this->formManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getInfo($property = NULL, $default = NULL) {
    static $themes;
    if (!isset($themes)) {
      $themes = $this->themeHandler->listInfo();
    }

    $name = $this->getName();
    $info = isset($themes[$name]->info) ? $themes[$name]->info : [];
    if (isset($property)) {
      return isset($info[$property]) ? $info[$property] : $default;
    }

    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->extension->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->extension->getPath();
  }

  /**
   * {@inheritdoc}
   */
  public function getPendingUpdates() {
    $pending = [];

    // Only continue if the theme is Bootstrap based.
    if ($this->isPlus()) {
      $current_theme = $this->getName();
      $schemas = $this->getSetting('schemas', []);
      foreach ($this->getAncestry() as $ancestor) {
        $ancestor_name = $ancestor->getName();
        if (!isset($schemas[$ancestor_name])) {
          $schemas[$ancestor_name] = \Drupal::CORE_MINIMUM_SCHEMA_VERSION;
          $this->setSetting('schemas', $schemas);
        }
        $pending_updates = $ancestor->getUpdateManager()->getPendingUpdates($current_theme === $ancestor_name);
        foreach ($pending_updates as $schema => $update) {
          if ((int) $schema > (int) $schemas[$ancestor_name]) {
            $pending[] = $update;
          }
        }
      }
    }

    return $pending;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreprocessManager() {
    return $this->preprocessManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrerenderManager() {
    return $this->prerenderManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessManager() {
    return $this->processManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider($provider = NULL) {
    // Only continue if the theme is Bootstrap based.
    if ($this->isPlus()) {
      $provider = $provider ?: $this->getSetting('cdn_provider');
      if ($this->providerManager->hasDefinition($provider)) {
        return $this->providerManager->createInstance($provider, ['theme' => $this]);
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviderManager() {
    return $this->providerManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getProviders() {
    $providers = [];

    // Only continue if the theme is Bootstrap based.
    if ($this->isPlus()) {
      foreach (array_keys($this->providerManager->getDefinitions()) as $provider) {
        if ($provider === 'none') {
          continue;
        }
        $providers[$provider] = $this->providerManager->createInstance($provider, ['theme' => $this]);
      }
    }

    return $providers;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name, $default = NULL) {
    $value = $this->settings()->get($name);
    return !isset($value) ? $default : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingManager() {
    return $this->settingManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingPlugin($name = NULL) {
    $settings = [];

    // Only continue if the theme is Bootstrap based.
    if ($this->isPlus()) {
      foreach (array_keys($this->settingManager->getDefinitions()) as $setting) {
        $settings[$setting] = $this->settingManager->createInstance($setting);
      }
    }

    // Return a specific setting plugin.
    if (isset($name)) {
      return isset($settings[$name]) ? $settings[$name] : NULL;
    }

    // Return all setting plugins.
    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getStorage() {
    static $cache = [];
    $theme = $this->getName();
    if (!isset($cache[$theme])) {
      $cache[$theme] = new Storage($theme);
    }
    return $cache[$theme];
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplateManager() {
    return $this->templateManager;
  }


  /**
   * {@inheritdoc}
   */
  public function getThemeHooks($existing, $type, $theme, $path) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->getInfo('name') ?: $this->getName();
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdateManager() {
    return $this->updateManager;
  }

  /**
   * {@inheritdoc}
   */
  public function includeOnce($file, $subdir = 'includes') {
    static $includes = [];
    $file = preg_replace('`^/?' . $this->getPath() . '/?`', '', $file);
    $file = strpos($file, '/') !== 0 ? $file = "/$file" : $file;
    $subdir = is_string($subdir) && !empty($subdir) && strpos($subdir, '/') !== 0 ? $subdir = "/$subdir" : '';
    $include = DRUPAL_ROOT . '/' . $this->getPath() . $subdir . $file;
    if (!isset($includes[$include])) {
      $includes[$include] = !!@include_once $include;
      if (!$includes[$include]) {
        drupal_set_message(t('Could not include file: @include', ['@include' => $include]), 'error');
      }
    }
    return $includes[$include];
  }

  /**
   * {@inheritdoc}
   */
  public function initialize() {
    // Immediately return if already initialized or not an Plus based theme.
    if ($this->initialized || !$this->isPlus()) {
      return;
    }

    // Create the necessary plugin managers for this theme.
    $this->alterManager = new AlterManager($this);
    $this->formManager = new FormManager($this);
    $this->preprocessManager = new PreprocessManager($this);
    $this->processManager = new ProcessManager($this);
    $this->prerenderManager = new PrerenderManager($this);
    $this->providerManager = new ProviderManagerProvider($this);
    $this->settingManager = new SettingManagerProvider($this);
    $this->updateManager = new UpdateManagerProvider($this);

    // Include any deprecated procedural files/functions.
    foreach ($this->getAncestry() as $ancestor) {
      if ($ancestor->getSetting('include_deprecated')) {
        foreach ($ancestor->fileScan('/^deprecated\.php$/') as $file) {
          $ancestor->includeOnce($file->uri, FALSE);
        }
      }
    }

    // Indicate that the theme has been initialized so it's not executed again.
    $this->initialized = TRUE;
  }

  /**
   * Installs a theme.
   */
  public function install() {
    // Immediately return if theme is not a plus based theme.
    if (!$this->isPlus()) {
      return;
    }

    // Only install the theme if it's Plus based and there are no schemas
    // currently set.
    if (!$this->getSetting('schemas')) {
      try {
        $schemas = [];
        foreach ($this->getAncestry() as $ancestor) {
          $schemas[$ancestor->getName()] = $ancestor->getUpdateManager()->getLatestSchema();
        }
        $this->setSetting('schemas', $schemas);
      }
      catch (\Exception $e) {
        // Intentionally left blank.
        // @see https://www.drupal.org/node/2697075
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function isPlus() {
    return in_array('plus', $this->getInfo('dependencies', []));
  }

  /**
   * {@inheritdoc}
   */
  public function isDev() {
    if (!isset($this->dev)) {
      $this->dev = !!Settings::get('theme.dev');
    }
    return $this->dev;
  }

  /**
   * {@inheritdoc}
   */
  public function livereloadUrl() {
    if (!isset($this->livereload)) {
      // Determine the URL for livereload, if set.
      $this->livereload = '';
      if ($livereload = Settings::get('theme.livereload')) {
        // If TRUE, set the port to the default used by grunt-contrib-watch.
        if ($livereload === TRUE) {
          $livereload = '//127.0.0.1:35729/livereload.js';
        }
        // If an integer, assume it's a port.
        elseif (is_int($livereload)) {
          $livereload = "//127.0.0.1:$livereload/livereload.js";
        }
        // If it's scalar, attempt to parse the URL.
        elseif (is_scalar($livereload)) {
          try {
            $livereload = Url::fromUri($livereload)->toString();
          }
          catch (\Exception $e) {
            $livereload = '';
          }
        }

        // Typecast livereload URL to a string.
        $this->livereload = "$livereload" ?: '';
      }
    }
    return $this->livereload;
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeActivate(ThemeEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeActivated(ThemeEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeInstall(ThemeEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeInstalled(ThemeEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeUninstall(ThemeEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function onThemeUninstalled(ThemeEvent $event) {
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessVariables() {
    // Build global preprocess template variables.
    if (!isset($this->preprocessVariables)) {
      $variables = [];

      // Add an "is_front" variable back to all templates.
      // @see https://www.drupal.org/node/2829588
      $variables['is_front'] = \Drupal::service('path.matcher')->isFrontPage();
      $variables['#cache']['contexts'][] = 'url.path.is_front';

      // Add active theme context.
      $variables['theme'] = $this->getInfo();
      $variables['theme']['dev'] = $this->isDev();
      $variables['theme']['livereload'] = $this->livereloadUrl();
      $variables['theme']['name'] = $this->getName();
      $variables['theme']['path'] = $this->getPath();
      $variables['theme']['title'] = $this->getTitle();
      $variables['theme']['settings'] = $this->settings()->get();
      $variables['theme']['query_string'] = \Drupal::getContainer()->get('state')->get('system.css_js_query_string') ?: '0';

      // Store global preprocess template variables.
      $this->preprocessVariables = $variables;
    }

    return $this->preprocessVariables;
  }

  /**
   * {@inheritdoc}
   */
  public function removeSetting($name) {
    $this->settings()->clear($name)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($name, $value) {
    $this->settings()->set($name, $value)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function settings() {
    static $themes = [];
    $name = $this->getName();
    if (!isset($themes[$name])) {
      $themes[$name] = new ThemeSettings($this);
    }
    return $themes[$name];
  }

  /**
   * {@inheritdoc}
   */
  public function subthemeOf($theme) {
    $name = $this->getName();
    return (string) $theme === $name || in_array((string) $theme, array_keys($this->getAncestry($name)));
  }

  /**
   * {@inheritdoc}
   */
  public function uninstall() {
  }

}
