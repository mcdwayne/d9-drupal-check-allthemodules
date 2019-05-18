<?php

namespace Drupal\config_refresh\Form;

use Drupal\config_refresh\ConfigRefreshManager;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBase;;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Extension\ExtensionDiscovery;

/**
 * Provides a form for exporting a single configuration file.
 */
class ConfigRefreshForm extends FormBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Tracks the valid config entity type definitions.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $definitions = [];

  /**
   * Constructs a new ConfigSingleImportForm.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   The config storage.
   */
  public function __construct(EntityManagerInterface $entity_manager, StorageInterface $config_storage, ConfigRefreshManager $config_refresh_manager) {
    $this->entityManager = $entity_manager;
    $this->configStorage = $config_storage;
    $this->configRefreshManager = $config_refresh_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('config.storage'),
      $container->get('config_refresh.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'config_refresh_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $module = NULL, $config_type = NULL, $config_name = NULL) {
    $config_types = [
        'system.simple' => $this->t('Simple configuration'),
      ] + $this->getEntityTypes();

    $form['module'] = [
      '#title' => $this->t('Module'),
      '#type' => 'select',
      '#options' => $this->getModuleList(),
      '#default_value' => $module,
      '#ajax' => [
        'callback' => '::updateConfigurationType',
        'wrapper' => 'edit-config-type-wrapper',
      ],
    ];
    $form['config_type'] = [
      '#title' => $this->t('Configuration type'),
      '#type' => 'select',
      '#options' => $config_types,
      '#default_value' => $config_type,
      '#ajax' => [
        'callback' => '::updateConfigurationName',
        'wrapper' => 'edit-config-name-wrapper',
      ],

      '#prefix' => '<div id="edit-config-type-wrapper">',
      '#suffix' => '</div>',
    ];
    $default_module = $form_state->getValue('module', $module);
    $default_type = $form_state->getValue('config_type', $config_type);
    $form['config_name'] = [
      '#title' => $this->t('Configuration name'),
      '#type' => 'select',
      '#options' => $this->findConfiguration($default_module, $default_type),
      '#default_value' => $config_name,
      '#prefix' => '<div id="edit-config-name-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh'),
    ];

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('config_name') != 'all') {
      $this->configRefreshManager->refreshById($form_state->getValue('module'), $form_state->getValue('config_type'), $form_state->getValue('config_name'));
    }
    else {
      $this->configRefreshManager->refreshAsBatch($form_state->getValue('module'), $form_state->getValue('config_type'));
    }

    drupal_set_message('Updated configuration successfully.');
  }

  public function updateConfigurationType($form, FormStateInterface $form_state) {
    $form['config_type']['#options'] = $this->findConfigurationType($form_state->getValue('module'));
    return $form['config_type'];
  }

  /**
   * Handles switching the configuration type selector.
   */
  public function updateConfigurationName($form, FormStateInterface $form_state) {
    $form['config_name']['#options'] = $this->findConfiguration($form_state->getValue('module'), $form_state->getValue('config_type'));
    return $form['config_name'];
  }

  protected function findConfigurationType($module) {
    $config_types =  $this->configRefreshManager->findConfigurationTypesLabels($module);
    return ['all' => $this->t('- All -')] + $config_types;

  }

  /**
   * Handles switching the configuration type selector.
   */
  protected function findConfiguration($module, $config_type) {
    $names = [];
    // For a given entity type, load all entities.
    if ($config_type && $config_type !== 'system.simple') {
      $entity_storage = $this->entityManager->getStorage($config_type);
      $entity_ids = $this->configRefreshManager->getEntityIds($module, $config_type);
      foreach ($entity_storage->loadMultiple($entity_ids) as $entity) {
        $entity_id = $entity->id();
        $label = $entity->label() ?: $entity_id;
        $names[$entity_id] = $label;
      }
    }
    // Handle simple configuration.
    else {
      // Gather the config entity prefixes.
      $config_prefixes = array_map(function (EntityTypeInterface $definition) {
        return $definition->getConfigPrefix() . '.';
      }, $this->definitions);

      // Find all config, and then filter our anything matching a config prefix.
      $names = $this->configStorage->listAll();
      $names = array_combine($names, $names);
      foreach ($names as $config_name) {
        foreach ($config_prefixes as $config_prefix) {
          if (strpos($config_name, $config_prefix) === 0) {
            unset($names[$config_name]);
          }
        }
      }
    }
    if (!empty($names)) {
      $names = ['all' => $this->t('- All -')] + $names;
    }
    return $names;
  }

  protected function getModuleList() {
    $listing = new ExtensionDiscovery(\Drupal::root());
    $modules = $listing->scan('module');
    $module_list = array_keys($modules);
    return [array_combine($module_list, $module_list)];
  }

  protected function getEntityTypes() {
    foreach ($this->entityManager->getDefinitions() as $entity_type => $definition) {
      if ($definition->isSubclassOf('Drupal\Core\Config\Entity\ConfigEntityInterface')) {
        $this->definitions[$entity_type] = $definition;
      }
    }
    $entity_types = array_map(function (EntityTypeInterface $definition) {
      return $definition->getLabel();
    }, $this->definitions);
    // Sort the entity types by label, then add the simple config to the top.
    uasort($entity_types, 'strnatcasecmp');
    return $entity_types;
  }

}
