<?php

namespace Drupal\entityreference_rendered_widget\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldWidget\OptionsButtonsWidget;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for widgets provided by this module.
 */
abstract class EntityReferenceRenderedBase extends OptionsButtonsWidget implements ContainerFactoryPluginInterface {

  /**
   * Display modes available for target entity type.
   *
   * @var array
   */
  protected $displayModes;

  /**
   * Label display options.
   *
   * @var array
   */
  protected $labelOptions;

  /**
   * Referenced entity type.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * Referenced entity type.
   *
   * @var array
   */
  protected $fieldSettings;

  /**
   * Entity Display Repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    array $settings,
    array $third_party_settings,
    EntityDisplayRepositoryInterface $entityDisplayRepository,
    EntityTypeManagerInterface $entityTypeManager) {

    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);
    $this->entityDisplayRepository = $entityDisplayRepository;
    $this->entityTypeManager = $entityTypeManager;

    $this->fieldSettings = $this->getFieldSettings();
    $this->targetEntityType = $this->getFieldSetting('target_type');
    $this->displayModes = $this->entityDisplayRepository->getViewModes($this->targetEntityType);
    $this->displayModes['default'] = [
      'label' => 'Default',
    ];
    $this->labelOptions = [
      'before' => $this->t('Before rendered element'),
      'after' => $this->t('After rendered element'),
      'hidden' => $this->t('Hidden'),
    ];

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
      $container->get('entity_display.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'display_mode' => 'default',
      'label_display' => 'before',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = [];
    $settings = $this->settings;

    $modes = [];
    foreach ($this->displayModes as $mode_name => $mode) {
      $modes[$mode_name] = $mode['label'];
    }

    $elements['display_mode'] = [
      '#type' => 'select',
      '#title' => $this->t('Display mode used'),
      '#options' => $modes,
      '#default_value' => isset($settings['display_mode']) ? $settings['display_mode'] : 'default',
    ];
    $elements['label_display'] = [
      '#type' => 'select',
      '#title' => $this->t('Label display'),
      '#options' => $this->labelOptions,
      '#default_value' => isset($settings['label_display']) ? $settings['label_display'] : 'before',
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $settings = $this->getSettings();
    $display_mode = $settings['display_mode'];
    $label_display = $settings['label_display'];

    $summary[] = $this->t('Display mode: @mode', ['@mode' => $this->displayModes[$display_mode]['label']]);
    $summary[] = $this->t('Label display: @label_display', ['@label_display' => $this->labelOptions[$label_display]]);

    return $summary;
  }

}
