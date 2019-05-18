<?php

namespace Drupal\edit_in_place_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;

/**
 * Plugin implementation of the 'edit_in_place_field_reference_with_parent' formatter.
 *
 * @FieldFormatter(
 *   id = "edit_in_place_field_reference_with_parent",
 *   label = @Translation("Edit in place filtered by parent"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EditInPlaceFieldReferenceWithParentFormatter extends EntityReferenceLabelFormatter implements ContainerFactoryPluginInterface{

  /**
   * Cache about list of entities.
   *
   * @var array
   */
  protected $cacheList = [];

  /**
   * The entity manager service
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The form builder service
   *
   * @var FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Parent field which determine ids of parents entities.
   *
   * @var string
   */
  protected $parentFieldName;

  /**
   * Machine name of field that will replace the label.
   *
   * @var mixed|null
   */
  protected $labelSubstitution;

  /**
   * Machine name of field that will replace the parent label.
   *
   * @var mixed|null
   */
  protected $parentLabelSubstitution;
  /**
   * Construct a EditInPlaceFieldReferenceWithParentFormatter object
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service
   * @param FormBuilderInterface $form_builder
   *   The form builder service
   * @param LanguageManagerInterface $language_manager
   *   The lamguage manager service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings
    , EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->parentFieldName = $this->getSetting('reference_parent_field_name');
    $this->labelSubstitution = $this->getSetting('label_substitution');
    $this->parentLabelSubstitution = $this->getSetting('parent_label_substitution');
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
      $container->get('entity_type.manager'),
      $container->get('form_builder')
    );
  }

  /**
   * Get the list of entities to be choose.
   *
   * @param $entity_type
   *    Entity type (ex: node, taxonomy term).
   * @param $label_key
   *    Key name for the label field (ex: title for node, name for taxonomy...).
   * @param $parent_ids
   *    Parent entities ids to filter choices.
   *
   * @return array
   *    List of possible choices.
   */
  protected function getSelectableEntitiesList($entity_type, $label_key, $parent_ids, $langcode) {
    $list = [];
    foreach($parent_ids as $parent_id) {
      if (!isset($this->cacheList[$parent_id])) {
        $storage = $this->entityTypeManager->getStorage($entity_type);
        $ids = $storage->getQuery()
          ->condition('parent', $parent_id, '=')
          ->sort($label_key, 'ASC', $langcode)
          ->execute();

        $entities_list = $storage->loadMultiple($ids);

        foreach($entities_list as $entity) {
          $this->cacheList[$parent_id][$entity->id()] = $entity->label();
          if (!empty($this->labelSubstitution) && isset($entity->{$this->labelSubstitution}) && !empty($entity->{$this->labelSubstitution}->value)) {
            $this->cacheList[$parent_id][$entity->id()] = $entity->{$this->labelSubstitution}->value;
          }
        }
      }
      $list[$parent_id] = $this->cacheList[$parent_id];
    }
    return $list;
  }

  protected function getEntitiesLabel($entity_type, $entity_ids, $langcode) {
    $storage = $this->entityTypeManager->getStorage($entity_type);
    $entities = $storage->loadMultiple($entity_ids);
    $labels = [];
    foreach($entities as $entity) {
      try {
        $entity = $entity->getTranslation($langcode);
      }catch(\Exception $e){}
      $labels[$entity->id()] = $entity->label();
      if (!empty($this->parentLabelSubstitution) && isset($entity->{$this->parentLabelSubstitution}) && !empty($entity->{$this->parentLabelSubstitution}->value)) {
        $labels[$entity->id()] = $entity->{$this->parentLabelSubstitution}->value;
      }
    }
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (!\Drupal::currentUser()->hasPermission('edit in place field editing permission')) {
      return parent::viewElements($items, $langcode);
    }
    $elements = [];
    $choice_lists = [];
    $cardinality = 1;
    $entity_type = '';
    $parent_ids = [];

    if (!empty($items) ) {
      // determine the entities ids of $this->parentFieldName
      $parents = $items->getEntity()->{$this->parentFieldName}->getValue();
      foreach($parents as $parent) {
        if (isset($parent['target_id']) && !empty($parent['target_id'])) {
          $parent_ids[$parent['target_id']] = $parent['target_id'];
        }
      }

      // Get entity type (node, taxonomy_term...).
      $entity_type = $items->getFieldDefinition()->getSetting('target_type');

      // Get cardinality of current field.
      $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

      // Get target entity properties fields names.
      $label_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('label');

      $choice_lists = $this->getSelectableEntitiesList($entity_type, $label_key, $parent_ids, $langcode);
      $parent_labels = $this->getEntitiesLabel($entity_type, $parent_ids, $langcode);
    }
    $selected = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $parent_id = $entity->get('parent')->target_id;
      if (!empty($parent_id)) {
        $selected[$parent_id]['ids'][] = $entity->id();
        $entity_label = $entity->label();
        if (!empty($this->labelSubstitution) && isset($entity->{$this->labelSubstitution}) && !empty($entity->{$this->labelSubstitution}->value)) {
          $entity_label = $entity->{$this->labelSubstitution}->value;
        }
        $selected[$parent_id]['labels'][] = $entity_label;
        $selected[$parent_id]['entities'][] = $entity;
      }
    }

    // 'edit-in-place-replace-' + field_name + '-' + parent_entity_id + '-' + parent_entity_language_code
    $ajax_call_replace = 'edit-in-place-replace-'.$items->getFieldDefinition()->getName().'-'.$items->getEntity()->id().'-'.$items->getEntity()->language()->getId();

    // todo: manage fact that cardinality could be different than -1 and 1
    $elements[0] = [
      'field_container' => [
        '#attached' => [
          'library' => ['edit_in_place_field/edit_in_place'],
        ],
        '#type' => 'fieldset',
        '#title' => t('Edit'),
        '#attributes' => [
          'class' => ['edit-in-place-clickable', 'edit-in-place-clickable-init', $ajax_call_replace]
        ],
        'base_render' => [
          '#theme' => 'edit_in_place_reference_with_parent_label',
          '#entities' => $selected,
          '#entity_type' => $entity_type,
          '#field_name' => $items->getName(),
          '#entity_id' => $items->getEntity()->id(),
          '#lang_code' => $items->getEntity()->language()->getId(),
        ],
        'form_container' => $this->formBuilder->getForm('Drupal\edit_in_place_field\Form\EditInPlaceReferenceWithParentForm', [
          'choice_lists' => $choice_lists,
          'parent_labels' => $parent_labels,
          'selected' => $selected,
          'cardinality' => $cardinality,
          'entity_type' => $items->getEntity()->getEntityTypeId(),
          'entity_id' => $items->getEntity()->id(),
          'field_name' => $items->getName(),
          'ajax_replace' => $ajax_call_replace,
          'label_substitution' => $this->labelSubstitution,
        ])
      ]
    ];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $options = parent::defaultSettings();

    $options['reference_parent_field_name'] = '';
    $options['label_substitution'] = '';
    $options['parent_label_substitution'] = '';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['reference_parent_field_name'] = [
      '#type' => 'textfield',
      '#title' =>  $this->t('Parent field machine name'),
      '#description' =>  $this->t('Specify a field parent in order to explode and filter this field only by children.'),
      '#default_value' => $this->getSetting('reference_parent_field_name'),
      '#required' => TRUE,
    ];
    $form['label_substitution'] = [
      '#type' => 'textfield',
      '#title' =>  $this->t('Label substitution'),
      '#description' =>  $this->t('Specify a field that will replace the label at display.'),
      '#default_value' => $this->getSetting('label_substitution'),
    ];
    $form['parent_label_substitution'] = [
      '#type' => 'textfield',
      '#title' =>  $this->t('Parent label substitution'),
      '#description' =>  $this->t('Specify a field that will replace the parent field label at display.'),
      '#default_value' => $this->getSetting('parent_label_substitution'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $parent = $this->getSetting('reference_parent_field_name');
    if ($parent) {
      $summary[] = $this->t('Parent filter: @parent', [
        '@parent' => $parent,
      ]);
    }

    $label_substitution = $this->getSetting('label_substitution');
    if ($label_substitution) {
      $summary[] = $this->t('Label replace: @label_substitution', [
        '@label_substitution' => $label_substitution,
      ]);
    }
    $parent_label_substitution = $this->getSetting('parent_label_substitution');
    if ($parent_label_substitution) {
      $summary[] = $this->t('Parent label replace: @parent_label_substitution', [
        '@parent_label_substitution' => $parent_label_substitution,
      ]);
    }
    return $summary;
  }

}
