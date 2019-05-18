<?php

namespace Drupal\entity_overlay\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceFormatterBase;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'entity reference overlay' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_entity_overlay_formatter",
 *   label = @Translation("Rendered entity overlay"),
 *   description = @Translation("Display a view mode of the referenced entities and display another view mode of the rendered entity on click as an overlay."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceOverlayFormatter extends EntityReferenceFormatterBase implements ContainerFactoryPluginInterface {

  use EntityOverlayFormatterBase;

  // @todo review recursive render limit

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs a EntityReferenceEntityFormatter instance.
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
   *   Any third party settings settings.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->entityTypeManager = $entity_type_manager;
    $this->entityDisplayRepository = $entity_display_repository;
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
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'list_view_mode' => 'teaser',
      'overlay_view_mode' => 'default',
      // 'width' => 500,.
      'display_link' => FALSE,
      'link_title' => t('Read more'),
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    // @todo add settings: width, height, show 'open' link, library
    $elements['list_view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('List view mode'),
      '#default_value' => $this->getSetting('list_view_mode'),
      '#required' => TRUE,
    ];
    $elements['overlay_view_mode'] = [
      '#type' => 'select',
      '#options' => $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type')),
      '#title' => t('Overlay view mode'),
      '#default_value' => $this->getSetting('overlay_view_mode'),
      '#required' => TRUE,
    ];
    // $elements['width'] = [
    // '#type' => 'textfield',
    // '#title' => t('Width'),
    // '#description' => 'In pixels (e.g. 500) or percents (e.g. 100%)',
    // '#default_value' => $this->getSetting('width'),
    // '#required' => TRUE,
    // ];.
    $elements['display_link'] = [
      '#type' => 'checkbox',
      '#title' => t('Display an extra link to open the overlay?'),
      '#default_value' => $this->getSetting('display_link'),
    ];
    $elements['link_title'] = [
      '#type' => 'textfield',
      '#title' => t('Link title'),
    // @todo translate
      '#default_value' => $this->getSetting('link_title'),
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];

    $view_modes = $this->entityDisplayRepository->getViewModeOptions($this->getFieldSetting('target_type'));
    $list_view_mode = $this->getSetting('list_view_mode');
    $overlay_view_mode = $this->getSetting('overlay_view_mode');
    $summary[] = t('List rendered as @mode', ['@mode' => isset($view_modes[$list_view_mode]) ? $view_modes[$list_view_mode] : $list_view_mode]);
    $summary[] = t('Overlay rendered as @mode', ['@mode' => isset($view_modes[$overlay_view_mode]) ? $view_modes[$overlay_view_mode] : $overlay_view_mode]);
    // @todo add new settings to summary
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $list_view_mode = $this->getSetting('list_view_mode');
    $overlay_view_mode = $this->getSetting('overlay_view_mode');
    $display_link = $this->getSetting('display_link');
    $link_title = $this->getSetting('link_title');

    // @todo dependency injection
    $pathAliasManager = \Drupal::service('path.alias_manager');

    // Prepare settings that will be passed to javascript behaviours.
    $entitySettings = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      if (!$entity->isNew()) {
        $view_builder = $this->entityTypeManager->getViewBuilder($entity->getEntityTypeId());
        $elements[$delta] = [
          '#theme' => 'entity_overlay_list_item',
          '#entity_view' => $view_builder->view($entity, $list_view_mode, $entity->language()->getId()),
          '#entity_id' => $entity->id(),
          '#entity_type_id' => $entity->getEntityTypeId(),
          '#entity_overlay_link' => $this->getOverlayLink($entity, $overlay_view_mode, $link_title),
          '#display_link' => $display_link,
        ];

        // @todo review path structure for each content entity type
        $pathMatch = [
          $entity->getEntityTypeId() . '/' . $entity->id(),
          $pathAliasManager->getAliasByPath('/' . $entity->getEntityTypeId() . '/' . $entity->id()),
        ];
        $entitySettings[$entity->getEntityTypeId() . '_' . $entity->id()] = [
          'overlay_url' => $this->getOverlayUrl($entity, $overlay_view_mode)->toString(),
          'path_match' => $pathMatch,
        ];

        if (!empty($items[$delta]->_attributes)) {
          $elements[$delta]['#options'] += ['attributes' => []];
          $elements[$delta]['#options']['attributes'] += $items[$delta]->_attributes;
          // Unset field item attributes since they have been included in the
          // formatter output and shouldn't be rendered in the field template.
          unset($items[$delta]->_attributes);
        }
      }
      else {
        continue;
      }
      $elements[$delta]['#cache']['tags'] = $entity->getCacheTags();
    }

    // Container for loading entity content.
    $elements[] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'entity-overlay__container',
      ],
    ];

    // Attaching here instead of entity_overlay.libraries.yml.
    // @todo review { weight: n } in .libraries.yml for depedencies
    // https://www.drupal.org/project/drupal/issues/1945262
    // These dependencies are needed for behaviors.
    $elements['#attached']['library'][] = 'core/drupalSettings';
    $elements['#attached']['library'][] = 'core/drupal';
    $elements['#attached']['library'][] = 'core/jquery';
    $elements['#attached']['library'][] = 'core/jquery.once';
    $elements['#attached']['library'][] = 'entity_overlay/entity_overlay.behaviors';
    $elements['#attached']['drupalSettings'] = [
      'entity_overlay' => $entitySettings,
    ];
    // Ajax library must be declared after the behaviors.
    $elements['#attached']['library'][] = 'core/drupal.ajax';

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for entity types that have a view
    // builder.
    $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
    return \Drupal::entityManager()->getDefinition($target_type)->hasViewBuilderClass();
  }

}
