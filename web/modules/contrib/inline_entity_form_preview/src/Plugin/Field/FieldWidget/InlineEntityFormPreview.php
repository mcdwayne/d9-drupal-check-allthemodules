<?php

/**
 * @file
 *   Contains \Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormPreview.
 */

namespace Drupal\inline_entity_form_preview\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\Element;
use Drupal\inline_entity_form\Plugin\Field\FieldWidget\InlineEntityFormComplex;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inline_entity_form_preview\Service\PreviewBuilderInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple inline widget with preview.
 *
 * @FieldWidget(
 *   id = "inline_entity_form_preview",
 *   label = @Translation("Inline entity form (With preview)"),
 *   multiple_values = true,
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions"
 *   },
 * )
 */
class InlineEntityFormPreview extends InlineEntityFormComplex {

  /**
   * The preview builder service.
   *
   * @var \Drupal\inline_entity_form_preview\Service\PreviewBuilderInterface
   */
  protected $preview_builder;

  /**
   * Constructs a InlineEntityFormBase object.
   *
   * @param array $plugin_id
   *   The plugin_id for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The definition of the field to which the widget is associated.
   * @param array $settings
   *   The widget settings.
   * @param array $third_party_settings
   *   Any third party settings.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle info.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   *   The entity display repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   Module handler service.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityTypeBundleInfoInterface $entity_type_bundle_info,
    EntityTypeManagerInterface $entity_type_manager,
    EntityDisplayRepositoryInterface $entity_display_repository,
    ModuleHandlerInterface $module_handler,
    PreviewBuilderInterface $preview_builder
  ) {
    parent::__construct($plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings,
      $entity_type_bundle_info,
      $entity_type_manager,
      $entity_display_repository,
      $module_handler);

    $this->preview_builder = $preview_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['third_party_settings'],
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository'),
      $container->get('module_handler'),
      $container->get('inline_entity_form_preview.builder')
    );
  }


  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $defaults = parent::defaultSettings();
    $defaults += [
      'view_mode' => 'default!',
    ];

    return $defaults;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // Get the widget form from inline_entity_form.
    $element = parent::settingsForm($form, $form_state);

    // Get the field target type.
    $target_type_id = $this->getFieldSetting('target_type');

    // Get the display modes for this entity type.
    $view_modes = $this->entityDisplayRepository->getViewModes($target_type_id);

    // Get the key and label from the registered display modes.
    $options = ['default!' => $this->t('-- Default --')]
      + (empty($view_modes) ? [] : array_filter(array_map(function($mode){
        return $mode['status'] ? $mode['label'] : NULL;
      }, $view_modes)));

    // Add the display mode select for the preview.
    $element['view_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display Mode'),
      '#description' => $this->t('Select the display mode to be used for previewing content in the edit form.'),
      '#options' => $options,
      '#default_value' => $this->getSetting('view_mode'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);

    // Get the field target type.
    $target_type_id = $this->getFieldSetting('target_type');

    // Get the view mode widget setting.
    $view_mode = $this->getSetting('view_mode');

    // Get the view modes for this entity type.
    $entity_view_modes = $this->entityDisplayRepository->getViewModes($target_type_id);

    // Validate the view mode.
    $view_mode = in_array($view_mode, array_keys($entity_view_modes))
      && !empty($entity_view_modes[$view_mode]['status']) ? $view_mode : 'full';

    $element['entities']['#table_fields'] = [
      'preview' => [
        'label' => $this->t('preview'),
        'type' => 'callback',
        'callback' => [$this->preview_builder, 'view'],
        'callback_arguments' => [
          'variables' => $view_mode,
          'langcode' => $items->getParent()->getValue()->language()->getId(),
        ],
      ]
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    $element = parent::formMultipleElements($items, $form, $form_state);

    return $element;
  }
}
