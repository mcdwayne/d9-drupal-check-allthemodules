<?php

namespace Drupal\ds_chains\Plugin\DsField;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ds\Plugin\DsField\DsFieldBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a DS field that chains entity reference fields.
 *
 * @DsField(
 *   id = "ds_chains",
 *   deriver = "\Drupal\ds_chains\Derivative\ChainsDeriver",
 * )
 */
class ChainedField extends DsFieldBase {

  /**
   * Formatter manager.
   *
   * @var \Drupal\Core\Field\FormatterPluginManager
   */
  protected $formatterPluginManager;

  /**
   * Entity view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $entityViewBuilder;

  /**
   * Field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\ds_chains\Plugin\DsField\ChainedField $instance */
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setFormatterPluginManager($container->get('plugin.manager.field.formatter'));
    $instance->setEntityViewBuilder($container->get('entity_type.manager')->getViewBuilder($plugin_definition['target_entity_type']));
    $instance->setEntityFieldManager($container->get('entity_field.manager'));
    return $instance;
  }

  /**
   * Sets entity field manager.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager.
   */
  public function setEntityFieldManager(EntityFieldManagerInterface $entityFieldManager) {
    $this->entityFieldManager = $entityFieldManager;
  }

  /**
   * Sets entityViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityViewBuilderInterface $entityViewBuilder
   *   New value for entityViewBuilder.
   *
   * @return ChainedField
   *   Instance called.
   */
  public function setEntityViewBuilder(EntityViewBuilderInterface $entityViewBuilder) {
    $this->entityViewBuilder = $entityViewBuilder;
    return $this;
  }

  /**
   * Sets formatterPluginManager.
   *
   * @param \Drupal\Core\Field\FormatterPluginManager $formatterPluginManager
   *   New value for formatterPluginManager.
   *
   * @return $this
   */
  public function setFormatterPluginManager(FormatterPluginManager $formatterPluginManager) {
    $this->formatterPluginManager = $formatterPluginManager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm($form, FormStateInterface $form_state) {
    if ($instance = $this->getFormatterInstance()) {
      $element = $instance->settingsForm($form, $form_state);
    }
    if ($this->pluginDefinition['field_cardinality'] === FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      $element['chain_settings'] = [
        '#type' => 'container',
        '#weight' => -100,
      ];
      $element['chain_settings']['ui_limit'] = [
        '#type' => 'number',
        '#title' => $this->t('UI Limit'),
        '#description' => t('Enter a number to limit the number of items to print for the items in the outer reference field. Leave empty to display them all.'),
        '#default_value' => $this->configuration['chain_settings']['ui_limit'],
      ];
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary($settings) {
    if ($instance = $this->getFormatterInstance($settings)) {
      return $instance->settingsSummary();
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $field = $this->getFieldConfiguration();
    $field += ['settings' => []];
    $entity = $this->entity();
    $chained_field = $this->pluginDefinition['chained_field_name'];
    $field_name = $this->pluginDefinition['field_name'];
    if ($entity->get($field_name)->isEmpty()) {
      // Entity reference field is empty.
      return [];
    }
    $build = [];
    $cache = new CacheableMetadata();
    $empty = TRUE;

    $ui_limit = isset($this->configuration['chain_settings']['ui_limit']) ? $this->configuration['chain_settings']['ui_limit'] : NULL;

    /** @var \Drupal\Core\Field\FieldItemInterface $field_item */
    foreach ($entity->get($field_name) as $delta => $field_item) {
      if ($ui_limit && $delta >= $ui_limit) {
        break;
      }
      /** @var \Drupal\Core\Entity\ContentEntityInterface $chained_entity */
      if (!($chained_entity = $field_item->get('entity')->getValue()) || $chained_entity->get($chained_field)->isEmpty() || !$chained_entity->get($chained_field)->access('view')) {
        // Entity doesn't exist or chained field is empty.
        if ($chained_entity) {
          $cache->addCacheableDependency($chained_entity);
        }
        continue;
      }
      $empty = FALSE;
      $build[] = $this->entityViewBuilder->viewField(
        $chained_entity->get($chained_field), [
          'label' => 'hidden',
          'type' => $field['formatter'],
          'settings' => $field['settings'],
        ]
      );
      $cache->addCacheableDependency($chained_entity);
    }
    if ($empty) {
      return $cache;
    }
    $cache->applyTo($build);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function formatters() {
    $options = $this->formatterPluginManager->getOptions($this->pluginDefinition['chained_field_type']);
    $applicable_options = [];
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->pluginDefinition['target_entity_type'], $this->pluginDefinition['target_bundle']);
    foreach ($options as $option => $label) {
      $plugin_class = DefaultFactory::getPluginClass($option, $this->formatterPluginManager->getDefinition($option));
      if ($plugin_class::isApplicable($field_definitions[$this->pluginDefinition['chained_field_name']])) {
        $applicable_options[$option] = $label;
      }
    }
    return $applicable_options;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    if (!empty($this->configuration['entity'])) {
      return $this->pluginDefinition['bundle'] === $this->entity()->bundle() && $this->validViewMode();
    }
    // For manage display, there is no entity, but the bundle is available in
    // the configuration.
    return $this->pluginDefinition['bundle'] === $this->bundle() && $this->validViewMode();
  }

  /**
   * Checks that the view mode is valid.
   *
   * @return bool
   *   TRUE if allowed.
   */
  protected function validViewMode() {
    return in_array($this->viewMode(), $this->pluginDefinition['view_modes'], TRUE);
  }

  /**
   * Gets instance of formatter.
   *
   * @param array $settings
   *   Settings.
   *
   * @return \Drupal\Core\Field\FormatterInterface|null
   *   Formatter instance.
   */
  protected function getFormatterInstance(array $settings = []) {
    $field = $this->getFieldConfiguration();
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($this->pluginDefinition['target_entity_type'], $this->pluginDefinition['target_bundle']);
    $field_definition = $field_definitions[$this->pluginDefinition['chained_field_name']];
    $instance = NULL;
    if (!empty($field['formatter'])) {
      $instance = $this->formatterPluginManager->getInstance([
        'field_definition' => $field_definition,
        'view_mode' => $this->viewMode(),
        // No need to prepare, defaults have been merged in setComponent().
        'prepare' => TRUE,
        'configuration' => [
          'type' => $field['formatter'],
          'settings' => $settings ?: $this->getConfiguration(),
        ],
      ]);
    }
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return $this->configuration['field']['chained_field_title'];
  }

}
