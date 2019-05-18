<?php

namespace Drupal\entity_content_export\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Define entity content export settings form.
 */
class EntityContentExportSettings extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_type_manager,
    EntityTypeBundleInfoInterface $entity_type_bundle_info
  ) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityTypeBundleInfo = $entity_type_bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_content_export.settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="entity-content-export-settings">';
    $form['#suffix'] = '</div>';

    $entity_type_bundles = $this->getElementPropertyValue(
      'entity_type_bundles', $form_state
    );
    $form['entity_type_bundles'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type Bundles'),
      '#description' => $this->t('Select all entity type bundles that can be 
        exported.'),
      '#multiple' => TRUE,
      '#required' => TRUE,
      '#options' => $this->getEntityTypeBundleOptions(),
      '#default_value' => $entity_type_bundles,
      '#ajax' => [
        'event' => 'change',
        'method' => 'replace',
        'wrapper' => 'entity-content-export-settings',
        'callback' => [$this, 'ajaxReplaceCallback'],
      ],
    ];

    if (!empty($entity_type_bundles)) {
      $form['entity_bundle_configuration'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Entity Bundle Configurations'),
        '#tree' => TRUE,
      ];
      $configuration = $this->getElementPropertyValue(
        'entity_bundle_configuration', $form_state
      );
      foreach ($entity_type_bundles as $entity_type_bundle) {
        list($entity_type_id, $bundle_name) = explode(':', $entity_type_bundle);
        $definition = $this->entityTypeManager->getDefinition($entity_type_id);

        if (!isset($form['entity_bundle_configuration'][$entity_type_id])) {
          $form['entity_bundle_configuration'][$entity_type_id] = [
            '#type' => 'details',
            '#open' => TRUE,
            '#title' => $this->t('@label', ['@label' => $definition->getLabel()]),
            '#tree' => TRUE,
          ];
        }
        $form['entity_bundle_configuration'][$entity_type_id][$bundle_name] = [
          '#type' => 'details',
          '#open' => TRUE,
          '#title' => $this->t('@bundle_label', ['@bundle_label' => $bundle_name]),
          '#tree' => TRUE,
        ];
        $form['entity_bundle_configuration'][$entity_type_id][$bundle_name]['display_mode'] = [
          '#type' => 'select' ,
          '#title' => $this->t('Display Mode'),
          '#options' => $this->getEntityViewDisplayOptions($entity_type_id, $bundle_name, ['default']),
          '#empty_option' => $this->t(' - Default -'),
          '#default_value' => $configuration[$entity_type_id][$bundle_name]['display_mode']
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Get element property value.
   *
   * @param $property
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array|mixed|null
   */
  public function getElementPropertyValue($property, FormStateInterface $form_state) {
    return $form_state->hasValue($property)
      ? $form_state->getValue($property)
      : $this->getConfiguration()->get($property);
  }

  /**
   * Ajax replace callback.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   */
  public function ajaxReplaceCallback(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Get entity type definitions by interface.
   *
   * @param $interface
   *
   * @return array
   */
  protected function getEntityTypeDefinitionsByInterface($interface) {
    $definitions = [];

    foreach ($this->entityTypeManager->getDefinitions() as $entity_id => $definition) {
      if (!$definition instanceof $interface) {
        continue;
      }
      $definitions[$entity_id] = $definition;
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->cleanValues()->getValues();

    $this->getConfiguration()
      ->setData($values)
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get entity type bundle options.
   *
   * @return array
   */
  protected function getEntityTypeBundleOptions() {
    $definitions = $this->getEntityTypeDefinitionsByInterface(
      ContentEntityTypeInterface::class
    );
    $options = [];

    /** @var  ContentEntityTypeInterface $definition */
    foreach ($definitions as $entity_type_id => $definition) {
      $entity_label = $definition->getLabel()->render();

      $options[$entity_label] = [];
      foreach ($this->entityTypeBundleInfo->getBundleInfo($entity_type_id) as $bundle_id => $bundle) {
        $options[$entity_label]["{$entity_type_id}:{$bundle_id}"] = $bundle['label'];
      }
    }

    return $options;
  }

  /**
   * Get entity view display options.
   *
   * @param $entity_type
   * @param $bundle
   * @param array $exclude_modes
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityViewDisplayOptions($entity_type, $bundle, array $exclude_modes = array()) {
    $options = [];

    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display */
    foreach ($this->getEntityViewDisplay($entity_type, $bundle) as $display_id => $display) {
      $mode = $display->getMode();

      if (in_array($mode, $exclude_modes)) {
        continue;
      }
      $options[$display_id] = $mode;
    }

    return $options;
  }

  /**
   * Get entity view display.
   *
   * @param $entity_type
   * @param $bundle
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getEntityViewDisplay($entity_type, $bundle) {
    /** @var \Drupal\Core\Entity\Display\EntityViewDisplayInterface $view_display */
    $view_display = $this->entityTypeManager->getStorage('entity_view_display')
      ->loadByProperties([
        'bundle' => $bundle,
        'targetEntityType' => $entity_type,
      ]);

    return $view_display;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      $this->getFormId(),
    ];
  }

  /**
   * Get configuration object.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   */
  protected function getConfiguration() {
    return $this->config($this->getFormId());
  }
}
