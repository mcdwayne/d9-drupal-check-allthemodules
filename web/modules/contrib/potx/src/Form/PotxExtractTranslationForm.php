<?php

namespace Drupal\potx\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Class PotxExtractTranslationForm.
 *
 * @package Drupal\potx\Form
 */
class PotxExtractTranslationForm extends FormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\language\ConfigurableLanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs a new PotxExtractTranslationForm object.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(LanguageManagerInterface $language_manager, ModuleHandlerInterface $module_handler, ThemeHandlerInterface $theme_handler) {
    $this->languageManager = $language_manager;
    $this->moduleHandler = $module_handler;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('module_handler'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'potx_extract_transation';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $components = $this->generateComponentList();
    $this->buildComponentSelector($form, $components);

    // Generate translation file for a specific language if possible.
    $languages = $this->languageManager->getLanguages();
    if (count($languages) > 1 || !isset($languages['en'])) {
      // We have more languages, or the single language we have is not English.
      $options = ['n/a' => $this->t('Language independent template')];
      foreach ($languages as $langcode => $language) {
        // Skip English, as we should not have translations for this language.
        if ($langcode == 'en') {
          continue;
        }

        $options[$langcode] = $this->t('Template file for @langname translations', ['@langname' => $language->getName()]);
      }
      $form['langcode'] = [
        '#type' => 'radios',
        '#title' => $this->t('Template language'),
        '#default_value' => 'n/a',
        '#options' => $options,
        '#description' => $this->t('Export a language independent or language dependent (plural forms, language team name, etc.) template.'),
      ];
      $form['translations'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Include translations'),
        '#description' => $this->t('Include translations of strings in the file generated. Not applicable for language independent templates.'),
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Extract'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if (empty($form_state->getValue('component'))) {
      $form_state->setErrorByName('component', $this->t('You should select a component to export.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // This could take some time.
    @set_time_limit(0);
    $this->moduleHandler->loadInclude('potx', 'inc');
    $this->moduleHandler->loadInclude('potx', 'inc', 'potx.local');

    // Silence status messages.
    potx_status('set', POTX_STATUS_MESSAGE);

    // $form_state->getValue('component') either contains a specific file name
    // with path, or a directory name for a module/theme or a module suite.
    // Examples:
    // - modules/watchdog
    // - sites/all/modules/coder
    // - sites/all/modules/i18n/i18n.module
    // - themes/garland.
    $component = $form_state->getValue('component');
    $pathinfo = pathinfo($component);
    if (!isset($pathinfo['filename'])) {
      // The filename key is only available in PHP 5.2.0+.
      $pathinfo['filename'] = substr($pathinfo['basename'], 0, strrpos($pathinfo['basename'], '.'));
    }

    if (isset($pathinfo['extension'])) {
      // A specific module or theme file was requested (otherwise there should
      // be no extension).
      potx_local_init($pathinfo['dirname']);
      $files = _potx_explore_dir($pathinfo['dirname'] . '/', $pathinfo['filename']);
      $strip_prefix = 1 + strlen($pathinfo['dirname']);
      $outputname = $pathinfo['filename'];
    }
    // A directory name was requested.
    else {
      potx_local_init($component);
      $files = _potx_explore_dir($component . '/');
      $strip_prefix = 1 + strlen($component);
      $outputname = $pathinfo['basename'];
    }

    // Decide on template or translation file generation.
    $template_langcode = $translation_langcode = NULL;
    if ($form_state->hasValue('langcode') && ($form_state->getValue('langcode') != 'n/a')) {
      $template_langcode = $form_state->getValue('langcode');
      $outputname .= '.' . $template_langcode;
      if (!empty($form_state->getValue('translations'))) {
        $translation_langcode = $template_langcode;
        $outputname .= '.po';
      }
      else {
        $outputname .= '.pot';
      }
    }
    else {
      $outputname .= '.pot';
    }

    // Collect every string in affected files. Installer related strings are
    // discarded.
    foreach ($files as $file) {
      _potx_process_file($file, $strip_prefix);
    }
    potx_finish_processing('_potx_save_string');

    // Need to include full parameter list to get to passing the language codes.
    _potx_build_files(POTX_STRING_RUNTIME, POTX_BUILD_SINGLE, 'general', '_potx_save_string', '_potx_save_version', '_potx_get_header', $template_langcode, $translation_langcode);

    _potx_write_files($outputname, 'attachment');

    exit;
  }

  /**
   * Generate a hierarchical structured list of components.
   *
   * @return array
   *   Array in the directory structure identified.
   *    - 'normal'  keyed elements being subfolders
   *    - '#name'   elements being component objects for the 'name' component
   *    - '#-count' being the file count of all components in the directory
   */
  private function generateComponentList() {

    $components = [];

    // Get a list of all enabled modules and themes.
    $modules = $this->moduleHandler->getModuleList();
    $themes = $this->themeHandler->listInfo();
    /** @var \Drupal\Core\Extension\Extension[] $result */
    $result = array_merge($modules, $themes);
    foreach ($result as $component) {
      // Build directory tree structure.
      $path_parts = explode('/', $component->getPath());
      $dir =& $components;
      foreach ($path_parts as $dirname) {
        if (!isset($dir[$dirname])) {
          $dir[$dirname] = [];
        }
        $dir =& $dir[$dirname];
      }

      // Information about components in this directory.
      $dir['#' . $component->getExtensionFilename()] = $component;
      $dir['#-count'] = isset($dir['#-count']) ? $dir['#-count'] + 1 : 1;
    }

    return $components;
  }

  /**
   * Build a chunk of the component selection form.
   *
   * @param array $form
   *   Form to populate with fields.
   * @param array $components
   *   Structured array with components as returned by
   *   $this->generateComponentList().
   * @param string $dirname
   *   Name of directory handled.
   *
   * @return mixed
   *   A count of the number of components or void.
   */
  private function buildComponentSelector(array &$form, array &$components, $dirname = '') {

    // Pop off count of components in this directory.
    if (isset($components['#-count'])) {
      $component_count = $components['#-count'];
      unset($components['#-count']);
    }

    $dirkeys = array_keys($components);

    // A directory with one component.
    if (isset($component_count) && (count($components) == 1)) {
      $component = array_shift($components);
      $dirname = $component->getPath();
      $form[$this->getFormElementId('dir', $dirname)] = [
        '#type' => 'radio',
        '#title' => t('Extract from %name in the %directory directory', ['%directory' => $dirname, '%name' => $component->getName()]),
        '#description' => t('Generates output from all files found in this directory.'),
        '#default_value' => 0,
        '#return_value' => $dirname,
        // Get all radio buttons into the same group.
        '#parents' => ['component'],
      ];
      return;
    }

    // A directory with multiple components in it.
    if (preg_match('!/(modules|themes)\\b(/.+)?!', $dirname, $pathmatch)) {
      $t_args = ['@directory' => substr($dirname, 1)];
      if (isset($pathmatch[2])) {
        $form[$this->getFormElementId('dir', $dirname)] = [
          '#type' => 'radio',
          '#title' => t('Extract from all in directory "@directory"', $t_args),
          '#description' => t('To extract from a single component in this directory, choose the desired entry in the fieldset below.'),
          '#default_value' => 0,
          '#return_value' => substr($dirname, 1),
          // Get all radio buttons into the same group.
          '#parents' => ['component'],
        ];
      }
      $element = [
        '#type' => 'details',
        '#title' => t('Directory "@directory"', $t_args),
        '#open' => FALSE,
      ];
      $form[$this->getFormElementId('fs', $dirname)] =& $element;
    }
    else {
      $element =& $form;
    }

    foreach ($dirkeys as $entry) {
      // A component in this directory with multiple components.
      if ($entry[0] == '#') {
        // Component entry.
        $t_args = [
          '%directory' => $components[$entry]->getPath(),
          '%name'      => $components[$entry]->getName(),
          '%pattern'   => $components[$entry]->getName() . '.*',
        ];
        $element[$this->getFormElementId('com', $components[$entry]->getExtensionFilename())] = [
          '#type' => 'radio',
          '#title' => $this->t('Extract from %name', $t_args),
          '#description' => $this->t('Extract from files named %pattern in the %directory directory.', $t_args),
          '#default_value' => 0,
          '#return_value' => $components[$entry]->getExtensionPathname(),
          // Get all radio buttons into the same group.
          '#parents' => ['component'],
        ];
      }
      // A subdirectory we need to look into.
      else {
        $this->buildComponentSelector($element, $components[$entry], "$dirname/$entry");
      }
    }

    return count($components);
  }

  /**
   * Generate a sane form element ID for the current radio button.
   *
   * @param string $type
   *   Type of ID generated: 'fs' for fieldset, 'dir' for directory, 'com' for
   *   component.
   * @param string $path
   *   Path of file we generate an ID for.
   *
   * @return string
   *   The generated ID.
   */
  private function getFormElementId($type, $path) {
    return 'potx-' . $type . '-' . preg_replace('/[^a-zA-Z0-9]+/', '-', $path);
  }

}
