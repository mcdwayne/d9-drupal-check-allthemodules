<?php

namespace Drupal\local_translation\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LocalTranslationSettingsForm.
 *
 * @package Drupal\local_translation\Form
 */
class LocalTranslationSettingsForm extends ConfigFormBase {

  /**
   * Module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;
  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['local_translation.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'local_translation_settings_form';
  }

  /**
   * LocalTranslationSettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $handler
   *   Module handler.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $manager
   *   Entity field manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $handler, EntityFieldManagerInterface $manager) {
    parent::__construct($config_factory);
    $this->moduleHandler = $handler;
    $this->entityFieldManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * Get config.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   An editable configuration object if the given name is listed in the
   *   getEditableConfigNames() method or an immutable configuration object if
   *   not.
   */
  private function getConfig() {
    $config_names = $this->getEditableConfigNames();
    return $this->config(reset($config_names));
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $default = $this->getConfig()->get('field_name');
    if (!isset($default) || empty($default)) {
      $default = '';
    }
    $form['field_name'] = [
      '#type'          => 'select',
      '#title'         => $this->t('Language combination field'),
      '#description'   => $this->t('Specify a language combination field to be used from the user entity'),
      '#options'       => $this->buildFieldsList('user'),
      '#default_value' => $default,
    ];

    if ($this->moduleHandler->moduleExists('local_translation_content')) {
      $form['local_translation_content'] = [
        '#type' => 'details',
        '#title' => $this->t('Local Translation Content'),
        '#open'  => TRUE,
      ];
      $form['local_translation_content']['enable_filter_translation_tab_to_skills'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Filter translation tab to users translation skills'),
        '#default_value' => $this->getConfig()->get('enable_filter_translation_tab_to_skills'),
      ];
      $form['local_translation_content']['always_display_original_language_translation_tab'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Always display original language in translation tab'),
        '#default_value' => $this->getConfig()->get('always_display_original_language_translation_tab'),
        '#states' => [
          'visible' => [
            ":input[name=\"enable_filter_translation_tab_to_skills\"]" => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
      $form['local_translation_content']['enable_missing_skills_warning'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Provide warning message on pages filtered by translation skills when user have not yet registered any translation skills'),
        '#default_value' => $this->getConfig()->get('enable_missing_skills_warning'),
      ];
      $form['local_translation_content']['enable_auto_preset_source_language_by_skills'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Automatically preset source language to the users translation skills'),
        '#default_value' => $this->getConfig()->get('enable_auto_preset_source_language_by_skills'),
      ];
      $form['local_translation_content']['enable_local_translation_content_permissions'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Enable Local Translation Content permissions'),
        '#default_value' => $this->getConfig()->get('enable_local_translation_content_permissions'),
      ];
      $form['local_translation_content']['enable_access_by_source_skills'] = [
        '#type'          => 'checkbox',
        '#title'         => $this->t('Only allow to translate if source language is a registered source skill'),
        '#description'   => $this->t('If the user only have permission to translate content into their translation skills, they can only translate it if any of their source translation skills are available.'),
        '#default_value' => $this->getConfig()->get('enable_access_by_source_skills'),
        '#states' => [
          'visible' => [
            ":input[name=\"enable_local_translation_content_permissions\"]" => [
              'checked' => TRUE,
            ],
          ],
        ],
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Build fields list of a given entity type.
   *
   * @param string $entity_type_id
   *   Entity type ID.
   *
   * @return array
   *   Fields list in machine_name => label format.
   */
  protected function buildFieldsList($entity_type_id) {
    /** @var \Drupal\Core\Entity\EntityFieldManager $service */
    $options = ['' => '- None -'];
    $fields  = $this->entityFieldManager->getFieldMapByFieldType('language_combination');
    $fields  = array_keys($fields[$entity_type_id]);
    if (!empty($fields)) {
      $definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
      foreach ($fields as $field) {
        /** @var \Drupal\field\Entity\FieldStorageConfig $definition */
        $definition = $definitions[$field];
        $config_id = "field.field.$entity_type_id." . $definition->id();
        $config = $this->config($config_id);
        if (!empty($config)) {
          $options[$field] = $this->config($config_id)->get('label');
        }
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_names = $this->getEditableConfigNames();
    $conf = $this->configFactory()->getEditable(reset($config_names));
    // Save general config(s).
    $conf->set('field_name', $form_state->getValue('field_name'))->save();
    // Save configs for Local Translation Content module.
    if ($this->moduleHandler->moduleExists('local_translation_content')) {
      $conf
        ->set(
          'enable_filter_translation_tab_to_skills',
          (bool) $form_state->getValue('enable_filter_translation_tab_to_skills'))
        ->set(
          'always_display_original_language_translation_tab',
          (bool) $form_state->getValue('always_display_original_language_translation_tab'))
        ->set(
          'enable_missing_skills_warning',
          (bool) $form_state->getValue('enable_missing_skills_warning'))
        ->set(
          'enable_auto_preset_source_language_by_skills',
          (bool) $form_state->getValue('enable_auto_preset_source_language_by_skills'))
        ->set(
          'enable_access_by_source_skills',
          (bool) $form_state->getValue('enable_access_by_source_skills'))
        ->set(
          'enable_local_translation_content_permissions',
          (bool) $form_state->getValue('enable_local_translation_content_permissions'))
        ->save();
    }
    parent::submitForm($form, $form_state);
  }

}
