<?php

namespace Drupal\reference_as_fields_formatter\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Cache\CacheableMetadata;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'raff_merge_into_content_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "merge_into_content_formatter",
 *   label = @Translation("Fields in parent content"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   }
 * )
 */
class MergeReferenceIntoContentFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * @var EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a MergeReferenceIntoContentFormatter object.
   *
   * @param string $plugin_id
   *   The plugin_id for the formatter.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the formatter is associated.
   * @param array $settings
   *   The formatter settings.
   * @param string $label
   *   The formatter label display setting.
   * @param string $view_mode
   *   The view mode.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param EntityManagerInterface $entity_manager
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityManagerInterface $entity_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityManager = $entity_manager;
  }


  public function settingsForm(array $form, FormStateInterface $form_state) {
    $view_modes = $this->getConfigurableViewModes();
    if (!empty($view_modes)) {
      $form['view_mode'] = [
        '#title' => t('View Mode'),
        '#description' => t('Select the view mode which will control which fields are shown and the display settings of those fields.'),
        '#type' => 'select',
        '#default_value' => $this->getSettings()['view_mode'],
        '#options' => $view_modes,
      ];
    }
    $form['show_entity_label'] = [
      '#title' => t('Display Entity Label'),
      '#description' => t('Should the label of the target entity be displayed in the table?'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_entity_label'),
    ];

    return $form;
  }

  public function settingsSummary() {
    $return = ['#markup' => $this->t('Fields are rendered in @view_mode view-mode.', ['@view_mode' => $this->getSetting('view_mode')])];
    if ($this->getSetting('show_entity_label')) {
      $return['#markup'] .= ' ' . $this->t('with a label');
    }
    return $return;
  }


  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $output = [];
    if ($entities = $this->getEntitiesForViewing($items, $langcode)) {
      $fieldDefinition = $items[0]->getFieldDefinition();
      $output += $this->getRenderArray($this->getTargetEntityId($fieldDefinition), $this->getTargetBundleId($fieldDefinition), $entities);
      $this->buildCacheMetadata($entities)->applyTo($output);
    }
    return $output;
  }


  /**
   * @param $type
   * @param $bundle
   * @param $view_mode
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function getRendererForDisplay($type, $bundle, $view_mode) {
    $bundle = $bundle ?: $type;
    $storage = $this->entityManager->getStorage('entity_view_display');
    $renderer = $storage->load(implode('.', [$type, $bundle, $view_mode]));
    if (!$renderer) {
      $renderer = $storage->load(implode('.', [$type, $bundle, 'default']));
    }
    return $renderer;
  }


  public function getEntitiesForViewing(FieldItemListInterface $items) {
    $entity_type = $this->getTargetEntityId($this->fieldDefinition);
    $entity_storage = $this->entityManager->getStorage($entity_type);
    $entities = [];
    foreach ($items as $item) {
      $entity = $entity_storage->load($this->getEntityIdFromFieldItem($item));
      if ($entity->access('view')) {
        $entities[] = $entity;
      }
    }
    return $entities;
  }

  /**
   * Get the render array for the given entities.
   *
   * @param $type
   * @param $bundle
   * @param $entities
   *
   * @return array
   */
  public function getRenderArray($type, $bundle, $entities) {
    $displayRenderer = $this->getRendererForDisplay($type, $bundle, $this->getSetting('view_mode'));
    $renderCandidates = $displayRenderer->buildMultiple($entities);
    $renderCandidates = reset($renderCandidates);
    $renderCandidates = array_filter($renderCandidates, [
      $this,
      'fieldIsRenderableContent',
    ]);
    if (!$this->getSetting('show_entity_label')) {
      $labelField = $this->entityManager->getDefinition($type)->getKey('label');
      unset($renderCandidates[$labelField]);
    }
    uasort($renderCandidates, [SortArray::class, 'sortByWeightProperty',]);
    return $renderCandidates;
  }

  /**
   * @param array $entities
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   */
  public function buildCacheMetadata($entities) {
    $cache_metadata = new CacheableMetadata();
    foreach ($entities as $entity) {
      // Todo check if the entity is needed for viewing so the cache tags make some sense
      $cache_metadata->addCacheableDependency($entity);
      $cache_metadata->addCacheableDependency($entity->access('view', NULL, TRUE));
    }
    return $cache_metadata;

  }

  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return array|mixed
   * @throws \Exception
   */
  protected function getTargetBundleId(FieldDefinitionInterface $field_definition) {
    $fieldDefinitionSettings = $field_definition->getSettings();
    if (strpos($fieldDefinitionSettings['handler'], 'default') === 0) {
      // Default to the first bundle, currently only supporting a single bundle.
      $target_bundle = array_values($fieldDefinitionSettings['handler_settings']['target_bundles']);
      $target_bundle = array_shift($target_bundle);
    }
    else {
      throw new \Exception('Using non-default reference handlers are not supported');
    }
    return $target_bundle;
  }


  /**
   * Check if the field is renderable.
   * Todo Check if this actually is enough.
   *
   * @param $field
   *
   * @return bool
   */
  protected function fieldIsRenderableContent($field) {
    return (isset($field['#items']) && ($field['#items']->getFieldDefinition()
        ->getDisplayOptions('view')));
  }

  /**
   * @return array
   */
  protected function getConfigurableViewModes() {
    return $this->entityManager->getViewModeOptions($this->getTargetEntityId($this->fieldDefinition));
  }

  /**
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *
   * @return mixed
   */
  protected function getTargetEntityId(FieldDefinitionInterface $field_definition) {
    return $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');
  }

  /**
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *
   * @return mixed
   */
  protected function getEntityIdFromFieldItem(FieldItemInterface $item) {
    return $item->getValue()['target_id'];
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity.manager')
    );
  }

  public static function defaultSettings() {
    return [
      'view_mode' => 'default',
      'show_entity_label' => 0,
    ];
  }
}
