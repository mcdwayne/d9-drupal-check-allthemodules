<?php

namespace Drupal\library\Plugin\Field\FieldWidget;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\library\Entity\LibraryItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'library_item_field_widget' widget.
 *
 * @FieldWidget(
 *   id = "library_item_field_widget",
 *   label = @Translation("Library item widget"),
 *   field_types = {
 *     "library_item_field_type"
 *   }
 * )
 */
class LibraryItemFieldWidget extends WidgetBase implements ContainerFactoryPluginInterface {
  /**
   * The entity type bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The inline entity from handler.
   *
   * @var \Drupal\inline_entity_form\InlineFormInterface
   */
  protected $inlineFormHandler;

  /**
   * The entity display repository.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * Constructs an InlineEntityFormBase object.
   *
   * @param string $plugin_id
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
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, array $third_party_settings, EntityTypeBundleInfoInterface $entity_type_bundle_info, EntityTypeManagerInterface $entity_type_manager, EntityDisplayRepositoryInterface $entity_display_repository) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $third_party_settings);

    $this->entityTypeBundleInfo = $entity_type_bundle_info;
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
      $configuration['third_party_settings'],
      $container->get('entity_type.bundle.info'),
      $container->get('entity_type.manager'),
      $container->get('entity_display.repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items->get($delta);
    $form_state->set('library_item', TRUE);
    if ($item->target_id && !$item->entity) {
      $element['warning']['#markup'] = $this->t('Unable to load the referenced entity.');
      return $element;
    }
    /** @var \Drupal\library\Entity\LibraryItem $entity */
    $entity = $item->entity;
    $element['library'] = [
      '#type' => 'fieldset',
      '#title' => t('Item'),
      '#attributes' => ['class' => ['container-inline']],
    ];

    if ($entity) {
      $element['library']['item_id'] = [
        '#type' => 'hidden',
        '#value' => $entity->id(),
      ];

      $element['library']['#entity'] = [
        '#type' => 'hidden',
        '#value' => $entity,
      ];
    }

    $element['library']['barcode'] = [
      '#type' => 'textfield',
      '#size' => 22,
      '#default_value' => $entity ? $entity->get('barcode')->value : '',
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#title' => t('Barcode'),
    ];

    if ($this->generateBarcode()) {
      $element['library']['barcode']['#disabled'] = 'true';
      $element['library']['barcode']['#placeholder'] = t('Automatically generated');
    }

    $element['library']['in_circulation'] = [
      '#type' => 'checkbox',
      '#default_value' => $entity ? $entity->get('in_circulation')->value : 0,
      '#title' => t('Reference only'),
    ];
    $element['library']['library_status'] = [
      '#type' => 'hidden',
      '#default_value' => $entity ? $entity->get('library_status')->value : 0,
    ];
    $element['library']['notes'] = [
      '#type' => 'textfield',
      '#default_value' => $entity ? $entity->get('notes')->value : NULL,
      '#size' => '22',
      '#maxlength' => $this->getFieldSetting('max_length'),
      '#title' => t('Notes'),
    ];

    if (isset($item)) {
      $element['remove'] = [
        '#type' => 'checkbox',
        '#default_value' => 0,
        '#title' => t('Remove'),
      ];
    }

    return $element;
  }

  /**
   * Return whether to generate barcodes from config.
   *
   * @return bool
   *   Generate barcode.
   */
  protected function generateBarcode() {
    return $this->fieldDefinition->getSetting('barcode_generation');
  }

  /**
   * Find the highest barcode number.
   *
   * Fetches default if not in database.
   *
   * @return int
   *   Barcode.
   */
  public static function findHighestBarcode() {
    $databaseValue = NULL;
    $results = \Drupal::EntityQuery('library_item')
      ->sort('barcode', 'DESC')
      ->range(0, 1)
      ->execute();
    foreach ($results as $item) {
      $entity = LibraryItem::load($item);
      $databaseValue = $entity->get('barcode')->value;
    }

    if ($databaseValue && $databaseValue > 1) {
      return $databaseValue;
    }
    else {
      return \Drupal::config('library.settings')->get('barcode_starting_point');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function extractFormValues(FieldItemListInterface $items, array $form, FormStateInterface $form_state) {
    if ($this->isDefaultValueWidget($form_state)) {
      $items->filterEmptyItems();
      return;
    }

    $field_name = $this->fieldDefinition->getName();
    $submittedValues = $form_state->getValue($field_name);
    $sortedEntityReferences = [];
    foreach ($items as $delta => $value) {
      $formElement = NestedArray::getValue($form, [
        $field_name,
        'widget',
        $delta,
      ]);
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (isset($formElement['library']['#entity'], $formElement['library']['#entity']['#value'])) {
        $entity = $formElement['library']['#entity']['#value'];
        $sortedEntityReferences[$submittedValues[$delta]['_weight']] = [
          'target_id' => $entity->id(),
          'entity' => $entity,
        ];
      }
    }

    ksort($sortedEntityReferences);
    $sortedEntityReferences = array_values($sortedEntityReferences);

    $items->setValue($sortedEntityReferences);
    $items->filterEmptyItems();

    $widget_state = [
      'instance' => $this->fieldDefinition,
      'delete' => [],
      'entities' => [],
    ];
    foreach ($items as $delta => $value) {
      $widget_state['entities'][$delta] = [
        'entity' => $value->entity,
        'needs_save' => FALSE,
      ];
    }
    $form_state->set('library_item', $widget_state);
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $target_entity_type_id = $field_definition->getFieldStorageDefinition()
      ->getSetting('target_type');
    if ($target_entity_type_id == 'library_item') {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function formMultipleElements(FieldItemListInterface $items, array &$form, FormStateInterface $form_state) {
    // Taken from inline_entity_form.
    $element = parent::formMultipleElements($items, $form, $form_state);

    // If we're using ulimited cardinality we don't display one empty item. Form
    // validation will kick in if left empty which esentially means people won't
    // be able to submit w/o creating another entity.
    if (!$form_state->isSubmitted() && $element['#cardinality'] == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && $element['#max_delta'] > 0) {
      $max = $element['#max_delta'];
      unset($element[$max]);
      $element['#max_delta'] = $max - 1;
      $items->removeItem($max);
      // Decrement the items count.
      $field_name = $element['#field_name'];
      $parents = $element[0]['#field_parents'];
      $field_state = static::getWidgetState($parents, $field_name, $form_state);
      $field_state['items_count']--;
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }
    return $element;
  }

}
