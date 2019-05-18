<?php

namespace Drupal\entity_log\Form;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityLogConfigForm.
 *
 * @package Drupal\entity_log\Form
 */
class EntityLogConfigForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Entity\EntityFieldManager definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeBundleInfo definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfo
   */
  protected $bundleInfo;

    /**
     * EntityLogConfigForm constructor.
     *
     * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
     *   ConfigFactory.
     * @param EntityTypeManagerInterface $entity_type_manager
     *   EntityTypeManager.
     * @param EntityFieldManagerInterface $entity_field_manager
     *   EntityFieldManager.
     * @param EntityTypeBundleInfoInterface $entityTypeBundleInfo
     *   EntityTypeBundleInfo.
     */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeBundleInfoInterface $entityTypeBundleInfo) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->bundleInfo = $entityTypeBundleInfo;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'entity_log.configuration',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_log_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $all_bundles_info = $this->bundleInfo->getAllBundleInfo();
    $form['entity_log_config'] = array(
      '#title' => $this->t('Entity log'),
      '#description' => $this->t('Select on which fields you want to log changes on'),
      '#type' => 'vertical_tabs',
    );
    /* @var \Drupal\Core\Config\Entity\ConfigEntityType $configEntityType */
    foreach ($all_bundles_info as $entity => $bundle_array) {
      try {
        $entity_info = $this->entityTypeManager->getDefinition($entity);
        // Vertical tab.
        if ($entity_info->entityClassImplements(FieldableEntityInterface::class)) {
          $form[$entity_info->id()] = [
            '#type' => 'details',
            '#title' => $entity_info->getLabel(),
            '#group' => 'entity_log_config',
            '#tree' => TRUE,
          ];
          $config = $this->config('entity_log.configuration')->get($entity_info->id());
          foreach ($bundle_array as $bundle_name => $bundle) {
            $form[$entity][$bundle_name] = [
              '#type' => 'details',
              '#title' => $bundle['label'],
              '#open' => isset($config[$bundle_name]['fields']) ? array_filter($config[$bundle_name]['fields']) : FALSE,
            ];
            $base_fields = $this->entityFieldManager->getFieldDefinitions($entity, $bundle_name);
            $options = [];
            /** @var \Drupal\field\Entity\FieldConfig $field */
            foreach ($base_fields as $field) {
              /* @var \Drupal\Core\Field\FieldDefinitionInterface $fieldDefinition */
              $fieldDefinition = $field->getItemDefinition()
                ->getFieldDefinition();
              $field_name = $fieldDefinition->getName();
              $field_label = $fieldDefinition->getLabel();
              $options[$field_name] = $field_label;
            }
            $form[$entity][$bundle_name]['fields'] = [
              '#title' => t('Fields'),
              '#type' => 'checkboxes',
              '#description' => t('Select fields you would like to log on update'),
              '#options' => $options,
              '#default_value' => isset($config[$bundle_name]['fields']) ? $config[$bundle_name]['fields'] : [],
            ];
          }
        }
      }
      catch (PluginNotFoundException $e) {
        $this->logger('entity_log')->error($e->getMessage());
      }
    }
    $form['log_in_logger'] = [
      '#title' => $this->t('Log in logger (watchdog)'),
      '#type' => 'checkbox',
      '#default_value' => $this->configFactory()->get('entity_log.configuration')->get('log_in_logger'),
    ];
    $form['log_in_entity'] = [
      '#title' => $this->t('Log in Entity Log entity'),
      '#type' => 'checkbox',
      '#default_value' => $this->configFactory()->get('entity_log.configuration')->get('log_in_entity'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $all_bundles_info = $this->bundleInfo->getAllBundleInfo();
    $config = $this->config('entity_log.configuration');
    foreach ($all_bundles_info as $entity => $bundle_array) {
      try {
        /** @var \Drupal\Core\Entity\EntityTypeInterface $entity_info */
        $entity_info = $this->entityTypeManager->getDefinition($entity);
        // Vertical tab.
        if ($entity_info->entityClassImplements(FieldableEntityInterface::class)) {
          $config->set($entity_info->id(), $form_state->getValue($entity_info->id()));
        }
      }
      catch (PluginNotFoundException $e) {
        $this->logger('entity_log')->error($e->getMessage());
      }
    }
    $config->set('log_in_logger', $form_state->getValue('log_in_logger'));
    $config->set('log_in_entity', $form_state->getValue('log_in_entity'));
    $config->save();
  }

}
