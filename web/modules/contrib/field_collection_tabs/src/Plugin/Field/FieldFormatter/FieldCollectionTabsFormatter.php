<?php

namespace Drupal\field_collection_tabs\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'tabs' formatter.
 *
 * @FieldFormatter(
 *   id = "field_collection_tabs",
 *   label = @Translation("Tabs"),
 *   field_types = {
 *     "field_collection"
 *   }
 * )
 */
class FieldCollectionTabsFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * The entity display repository
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The entity field manager
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, EntityDisplayRepositoryInterface $entity_display_repository, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityDisplayRepository = $entity_display_repository; // Used to get all view modes for all entity types
    $this->entityFieldManager = $entity_field_manager; // Used to get all fields of the entity type
    $this->entityTypeManager = $entity_type_manager; // Used to get the render array of the entity
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
      $container->get('entity_display.repository'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'title_field' => FALSE,
      'view_mode' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = array();
    $options = array($this->t('No titles'));
    $field_definition = $this->fieldDefinition;
    $fields = $this->entityFieldManager->getFieldDefinitions('field_collection_item', $field_definition->getName());
    foreach ($fields as $field_name => $field) {
      if ($field->getFieldStorageDefinition()->isBaseField() == FALSE && $field->getType() == 'string') {
        $options[$field_name] = $this->t($field->getLabel());
      }
    }

    $elements['title_field'] = array(
      '#type' => 'select',
      '#title' => ('Field to use for tab titles'),
      '#description' => t('Select the field to use for tab titles'),
      '#default_value' => $this->getSetting('title_field'),
      '#options' => $options
    );

    $displays = $this->entityDisplayRepository->getAllViewModes();
    if (isset($displays['field_collection_item']) && !empty($displays['field_collection_item'])) {
      $displays = $displays['field_collection_item'];
      $options = array($this->t('Default'));
      foreach ($displays as $view_mode => $info) {
        $options[$view_mode] = $info['label'];
      }
      $elements['view_mode'] = array(
        '#type' => 'select',
        '#title' => t('View mode'),
        '#options' => $options,
        '#default_value' => $this->getSetting('view_mode'),
        '#description' => t('Select the view mode'),
      );
    }
    else {
      $elements['view_mode'] = array(
        '#markup' => $this->t('No custom view modes for Field Collection Items')
      );
    }


    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    $summary[] = $this->getSetting('title_field') ? $this->t('Title field: ' . $this->getSetting('title_field')) : $this->t('Numbered Tabs');
    $summary[] = $this->getsetting('view_mode')  ? $this->t('View Mode: ' . $this->getSetting('view_mode')) : $this->t('View Mode: Full');
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $titles = [];
    $tabs = [];
    $title_field = !empty($this->getSetting('title_field')) ? $this->getSetting('title_field') : FALSE;
    $view_mode = !empty($this->getSetting('view_mode')) ? $this->getSetting('view_mode') : 'full';

    foreach ($items as $delta => $item) {
      if ($item->value !== NULL) {
        $field_collection_item = $item->getFieldCollectionItem();
        $title = $field_collection_item->get($title_field)->value;

        // Preventing a tab from not having a title, seems bad to not have a title for a tab.
        if ($title == '' || $title_field == FALSE) {
          $title = "Tab " . ($delta + 1);
        }

        $titles[] = $title;

        $render_item = $this->entityTypeManager->getViewBuilder('field_collection_item')->view($field_collection_item, $view_mode);
        $tabs[] = $render_item;
      }
    }

    $render_array = [
      '#theme' => 'field_collection_tabs',
      '#titles' => $titles,
      '#tabs' => $tabs,
      '#field_name' => $title_field,
      '#attached' => [
        'library' => [
          'field_collection_tabs/field_collection_tabs',
        ],
      ],
    ];

    return $render_array;
  }

}
