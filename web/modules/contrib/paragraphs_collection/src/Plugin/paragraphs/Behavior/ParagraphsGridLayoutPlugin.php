<?php

namespace Drupal\paragraphs_collection\Plugin\paragraphs\Behavior;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs_collection\GridLayoutDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to define grid based layouts.
 *
 * @ParagraphsBehavior(
 *   id = "grid_layout",
 *   label = @Translation("Grid layout"),
 *   description = @Translation("Allows to choose pre-defined grid sets."),
 *   weight = 0
 * )
 */
class ParagraphsGridLayoutPlugin extends ParagraphsBehaviorBase {

  /**
   * Grid layout discovery service.
   *
   * @var \Drupal\paragraphs_collection\GridLayoutDiscoveryInterface
   */
  protected $gridLayoutDiscovery;

  /**
   * ParagraphsGridLayoutPlugin constructor.
   *
   * @param array $configuration
   *   The configuration array.
   * @param string $plugin_id
   *   This plugin id.
   * @param mixed $plugin_definition
   *   Plugin definition.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   Entity field manager service.
   * @param \Drupal\paragraphs_collection\GridLayoutDiscoveryInterface $grid_layout_discovery
   *   The grid discovery service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityFieldManager $entity_field_manager, GridLayoutDiscoveryInterface $grid_layout_discovery) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_field_manager);
    $this->gridLayoutDiscovery = $grid_layout_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('paragraphs_collection.grid_layout_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'paragraph_reference_field' => '',
      'available_grid_layouts' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $paragraphs_type = $form_state->getFormObject()->getEntity();

    if ($paragraphs_type->isNew()) {
      return [];
    }

    // The grid gets it's content from referenced entities (ERR or ER).
    $reference_field_options = [];
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('paragraph', $paragraphs_type->id());
    foreach ($field_definitions as $name => $definition) {
      if ($definition instanceof FieldConfigInterface) {
        if ($definition->getFieldStorageDefinition()->getCardinality() != 1 && in_array($definition->getType(), ['entity_reference', 'entity_reference_revisions'])) {
          $reference_field_options[$name] = $definition->getLabel();
        }
      }
    }

    if ($reference_field_options) {
      $form['paragraph_reference_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Grid field'),
        '#description' => $this->t('Field to be used as the grid container.'),
        '#options' => $reference_field_options,
        '#default_value' => $this->configuration['paragraph_reference_field'],
      ];
    }
    else {
      $url = Url::fromRoute('entity.paragraph.field_ui_fields', [
        $paragraphs_type->getEntityTypeId() => $paragraphs_type->id()
      ]);

      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('No paragraph reference field type available. Please add at least one in the <a href=":link">Manage fields</a> page.', [
          ':link' => $url->toString()
        ]),
        '#attributes' => [
          'class' => ['messages messages--error'],
        ],
      ];
    }

    // Select pre-defined grid layouts.
    if ($layout_options = $this->gridLayoutDiscovery->getLayoutOptions()) {
      $form['available_grid_layouts'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Grid layouts'),
        '#description' => $this->t('Layouts that will be available when creating paragraphs. Select none to allow displaying all layouts.'),
        '#options' => $layout_options,
        '#default_value' => $this->configuration['available_grid_layouts'],
        '#empty_value' => [],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if (!$form_state->getValue('paragraph_reference_field')) {
      $form_state->setErrorByName('message', $this->t('The grid layout plugin cannot be enabled if the paragraph reference field is missing.'));
    }

    if (!$form_state->getValue('available_grid_layouts')) {
      $form_state->setErrorByName('message', $this->t('The grid layout plugin cannot be enabled if no layouts are selected.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['paragraph_reference_field'] = $form_state->getValue('paragraph_reference_field');
    $this->configuration['available_grid_layouts'] = array_filter($form_state->getValue('available_grid_layouts'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $layout_options = [];
    $defined_layouts = $this->gridLayoutDiscovery->getLayoutOptions();
    if (!empty($config['available_grid_layouts'])) {
      foreach ($config['available_grid_layouts'] as $layout) {
        $layout_options[$layout] = $defined_layouts[$layout];
      }
    }
    else {
      $layout_options = $defined_layouts;
    }

    if ($layout_options) {
      $form['#attached']['library'][] = 'paragraphs_collection/plugin_admin';

      // Create a unique id for the wrapper.
      $wrapper_id = Html::getUniqueId('layout-wrapper');
      $form['layout_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['paragraphs-plugin-inline-container', 'paragraphs-layout-select'],
          'id' => $wrapper_id,
        ],
      ];
      $form['layout_wrapper']['layout'] = [
        '#type' => 'select',
        '#title' => $this->t('Layout'),
        // @todo Add the description about what happens if there are more items
        // than slots.
        // @todo Add a nice icon representing the layout.
        '#empty_option' => $this->t('- None -'),
        '#options' => $layout_options,
        '#default_value' => $paragraph->getBehaviorSetting($this->getPluginId(), 'layout'),
        '#attributes' => ['class' => ['paragraphs-plugin-form-element']],
      ];

    }
    else {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('No layouts available. See paragraphs_collection.api.php for more information on how to add them.'),
        '#attributes' => [
          'class' => ['messages messages--warning'],
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $paragraph->setBehaviorSettings($this->pluginId, $form_state->getValues()['layout_wrapper']);
  }

  /**
   * {@inheritdoc}
   */
  public function view(array &$build, Paragraph $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    // Everything else is done in ParagraphsGridLayoutPlugin::preprocess method.
    if ($grid_layout = $paragraph->getBehaviorSetting($this->getPluginId(), 'layout')) {
      $layouts = $this->gridLayoutDiscovery->getLibraries($grid_layout);
      if(!isset($build['#attached']['library'])) {
        $build['#attached']['library'] = [];
      }
      $build['#attached']['library'] = array_merge($layouts, $build['#attached']['library']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary(Paragraph $paragraph) {
    $summary = [];
    $layouts = $this->gridLayoutDiscovery->getGridLayouts();
    if ($layout = $paragraph->getBehaviorSetting($this->getPluginId(), 'layout')) {
      $summary = [
        [
          'label' => $this->t('Layout'),
          'value' => $layouts[$layout]['title']
        ]
      ];
    }
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocess(&$variables) {
    $config = $this->getConfiguration();

    if (!$layout = $variables['paragraph']->getBehaviorSetting($this->getPluginId(), 'layout')) {
      return;
    }

    $layout_config = $this->gridLayoutDiscovery->getLayout($layout);
    $field = $config['paragraph_reference_field'];

    // Add the the wrapper class.
    $variables['content'][$field]['#attributes']['class'] = $layout_config['wrapper_classes'];

    // Add children classes.
    $i = 0;
    $total_columns = count($layout_config['columns']);
    if (isset($variables['content'][$field]['#items'])) {
      foreach ($variables['content'][$field]['#items'] as $item) {
        // If there are more elements than columns we start over.
        if ($total_columns === $i) {
          $i = 0;
        }

        $item->_attributes = ['class' => $layout_config['columns'][$i]['classes']];
        $i++;
      }
    }
  }

}
