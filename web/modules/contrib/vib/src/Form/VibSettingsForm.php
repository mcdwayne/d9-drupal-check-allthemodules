<?php

namespace Drupal\vib\Form;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View in browser settings form.
 */
class VibSettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The library discovery service.
   *
   * @var \Drupal\Core\Asset\LibraryDiscoveryInterface
   */
  protected $libraryDiscovery;

  /**
   * The theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected $themeHandler;

  /**
   * Constructs an VibSettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Asset\LibraryDiscoveryInterface $discovery
   *   The library discovery service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler
   *   The theme handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory,
                              ModuleHandlerInterface $module_handler,
                              LibraryDiscoveryInterface $discovery,
                              ThemeHandlerInterface $theme_handler) {
    parent::__construct($config_factory);
    $this->moduleHandler = $module_handler;
    $this->libraryDiscovery = $discovery;
    $this->themeHandler = $theme_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('library.discovery'),
      $container->get('theme_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vib_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['vib.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vib.settings');

    $form['cron_job_time'] = [
      '#type' => 'select',
      '#title' => $this->t('Run cron job every'),
      '#description' => $this->t('Select the period of time when vib_link entities will be added to the delete queue.'),
      '#options' => $this->getCronTimeList(),
      '#default_value' => $config->get('cron_job_time'),
    ];

    // Fieldset for custom module configuration.
    $form['custom'] = [
      '#type' => 'details',
      '#title' => $this->t('Processors configuration'),
      '#description' => $this->t('Here you can split mails by module and key. Set drupal library to attach to the browser variant of the email and also set a lifetime of "View in browser" links.'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    // Configuration for a new module.
    $form['custom']['module'] = [
      '#type' => 'select',
      '#title' => $this->t('Module'),
      '#options' => $this->getModulesList(),
      '#empty_option' => $this->t('- Select -'),
    ];
    $form['custom']['module_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#description' => $this->t('The key is used to identify specific mails if the module sends more than one. Leave empty to use the configuration for all mails sent by the selected module.'),
      '#default_value' => '',
    ];
    $form['custom']['library'] = [
      '#type' => 'select',
      '#title' => $this->t('Drupal library'),
      '#options' => $this->getLibraryOptions(),
    ];
    $form['custom']['lifetime'] = [
      '#type' => 'select',
      '#title' => $this->t('Lifetime'),
      '#options' => $this->getLifetimeOptions(),
      '#description' => $this->t('The maximum time the link will be available.'),
    ];

    $form['custom']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#validate' => ['::validateAdd'],
      '#submit' => ['::submitAdd'],
      '#button_type' => 'primary',
    ];

    // Show and change all custom configurations.
    $form['custom']['modules'] = [
      '#type' => 'table',
      '#header' => [
        'module' => $this->t('Module'),
        'key' => $this->t('Key'),
        'library' => $this->t('Library'),
        'lifetime' => $this->t('Lifetime'),
        'remove' => $this->t('Remove'),
      ],
      '#empty' => $this->t('No specific configuration yet.'),
    ];

    // Get all configured modules and show them in a list.
    $modules = $config->get('modules') ?: [];
    foreach ($modules as $module => $module_settings) {
      if (is_array($module_settings) && $this->moduleHandler->moduleExists($module)) {
        // Main table structure.
        foreach ($module_settings as $key => $settings) {
          $module_key = $module . '.' . $key;
          $row = [
            'module' => ['#markup' => $this->moduleHandler->getName($module)],
            'key' => ['#markup' => $key == 'none' ? $this->t('All') : $key],
          ];

          $row['library'] = [
            '#type' => 'select',
            '#title' => $this->t('Library'),
            '#title_display' => 'hidden',
            '#options' => $this->getLibraryOptions(),
            '#default_value' => isset($settings['library']) ? $settings['library'] : '',
          ];
          $row['lifetime'] = [
            '#type' => 'select',
            '#title' => $this->t('Lifetime'),
            '#title_display' => 'hidden',
            '#options' => $this->getLifetimeOptions(),
            '#default_value' => isset($settings['lifetime']) ? $settings['lifetime'] : '',
          ];
          $row['remove'] = [
            '#type' => 'checkbox',
            '#default_value' => $module_key,
          ];
          $form['custom']['modules'][$module_key] = $row;
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateAdd(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue(['custom', 'module']) == '') {
      $form_state->setErrorByName('custom][module', $this->t('The module is required.'));
    }

    $config = $this->config('vib.settings');
    $config_key = $this->getModuleKeyConfigPrefix($form_state->getValue(['custom', 'module']), $form_state->getValue(['custom', 'module_key']));
    if ($config->get($config_key)) {
      $form_state->setErrorByName('custom][module', $this->t('An entry for this combination exists already. Use the form below to update or remove it.'));
      return;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitAdd(array &$form, FormStateInterface $form_state) {
    // Create a new module configuration or update an existing one if a module
    // is selected.
    $module = $form_state->getValue(['custom', 'module']);
    $key = $form_state->getValue(['custom', 'module_key']);
    $library = $form_state->getValue(['custom', 'library']);
    $lifetime = $form_state->getValue(['custom', 'lifetime']);

    $prefix = $this->getModuleKeyConfigPrefix($module, $key);

    $config = $this->config('vib.settings');
    // Create the new custom module configuration.
    if ($library) {
      $config->set($prefix . '.library', $library);
    }
    if ($lifetime) {
      $config->set($prefix . '.lifetime', $lifetime);
    }
    $config->save();

    drupal_set_message($this->t('The configuration has been added.'));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('vib.settings');

    $config->set('cron_job_time', $form_state->getValue('cron_job_time'));
    // Update or remove the custom modules.
    if ($form_state->hasValue(['custom', 'modules']) && is_array($form_state->getValue(['custom', 'modules']))) {
      foreach ($form_state->getValue(['custom', 'modules'], []) as $module_key => $settings) {
        $prefix = 'modules.' . $module_key;
        if (!empty($settings['remove'])) {
          // If the checkbox is checked, remove this row.
          $config->clear($prefix);
        }
        else {
          foreach ($settings as $type => $value) {
            if (!empty($settings[$type])) {
              $config->set($prefix . '.' . $type, $value);
            }
          }
        }
      }
    }

    // Finally save the configuration.
    $config->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Returns a list with all libraries.
   *
   * @return string[]
   *   List of libraries.
   */
  protected function getLibraryOptions() {
    $list = ['_none' => $this->t('- None -')];
    $active_theme_name = $this->themeHandler->getDefault();
    $libs = $this->libraryDiscovery->getLibrariesByExtension($active_theme_name);
    if (!empty($libs)) {
      foreach ($libs as $lib_name => $info) {
        $list["$active_theme_name/$lib_name"] = "$active_theme_name/$lib_name";
      }
    }

    foreach ($this->moduleHandler->getModuleList() as $name => $definition) {
      if (strpos($definition->getPath(), 'modules/custom') !== FALSE) {
        $libs = $this->libraryDiscovery->getLibrariesByExtension($name);
        if (!empty($libs)) {
          foreach ($libs as $lib_name => $info) {
            $list["$name/$lib_name"] = "$name/$lib_name";
          }
        }
      }
    }
    ksort($list);

    return $list;
  }

  /**
   * Returns a list with time.
   *
   * @return string[]
   *   List of time options.
   */
  protected function getLifetimeOptions() {
    $list = [
      '-1' => $this->t('- Permanent -'),
      '86400' => $this->t('1 day'),
      '604800' => $this->t('1 week'),
      '2628000' => $this->t('1 month'),
      '31536000' => $this->t('1 year'),
    ];
    $this->moduleHandler->alter('vib_lifetime_options', $list);
    ksort($list);

    return $list;
  }

  /**
   * Returns a list with all modules that send emails.
   *
   * Currently this is evaluated by the hook_mail implementation.
   *
   * @return string[]
   *   List of modules, keyed by the machine name.
   */
  protected function getModulesList() {
    $list = [];
    foreach ($this->moduleHandler->getImplementations('mail') as $module) {
      $list[$module] = $this->moduleHandler->getName($module);
    }
    asort($list);

    return $list;
  }

  /**
   * Returns a list of time for 'put in queue' cron job.
   *
   * @return string[]
   *   List of time options.
   */
  protected function getCronTimeList() {
    $list = [
      '3600' => $this->t('1 hour'),
      '10800' => $this->t('3 hours'),
      '21600' => $this->t('6 hours'),
      '86400' => $this->t('1 day'),
    ];

    return $list;
  }

  /**
   * Builds the config prefix for a given module and key pair.
   *
   * @param string $module
   *   The module name.
   * @param string $key
   *   The mail key.
   *
   * @return string
   *   The config prefix for the settings array.
   */
  protected function getModuleKeyConfigPrefix($module, $key) {
    $module_key = $module . '.' . ($key ?: 'none');
    $config_prefix = 'modules.' . $module_key;
    return $config_prefix;
  }

}
