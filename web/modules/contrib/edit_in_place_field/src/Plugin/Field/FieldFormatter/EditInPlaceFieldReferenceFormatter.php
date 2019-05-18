<?php

namespace Drupal\edit_in_place_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\views\Views;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;

/**
 * Plugin implementation of the 'edit_in_place_field_entity_reference' formatter.
 *
 * @FieldFormatter(
 *   id = "edit_in_place_field_entity_reference",
 *   label = @Translation("Edit in place"),
 *   field_types = {
 *     "entity_reference",
 *   }
 * )
 */
class EditInPlaceFieldReferenceFormatter extends EntityReferenceLabelFormatter implements ContainerFactoryPluginInterface{

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
   * Machine name of field that will replace the label.
   *
   * @var mixed|null
   */
  protected $labelSubstitution;

  /**
   * Construct a EditInPlaceFieldReferenceFormatter object
   *
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service
   * @param FormBuilderInterface $form_builder
   *   The form builder service
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings
    , EntityTypeManagerInterface $entity_type_manager, FormBuilderInterface $form_builder) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $form_builder;
    $this->labelSubstitution = $this->getSetting('label_substitution');
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
   * @param $bundle_key
   *    Bundle Key (type for nodes, vid for taxonomy...).
   * @param $bundles_type
   *    Bundles to ve listed (ex: "article" for nodes, "tags" for taxonomy...).
   * @param $label_key
   *    Key name for the label field (ex: title for node, name for taxonomy...).
   * @param $langcode
   *    Drupal Lang code for field.
   *
   * @return array
   *    List of possible choices.
   */
  protected function getSelectableEntitiesList($entity_type, $bundle_key, $bundles_type, $label_key, $langcode) {
    $cache_key = $entity_type.$bundle_key.implode('', $bundles_type).$label_key;
    $list = [];
    if (!isset($this->cacheList[$cache_key])) {
      /** @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info_service */
      $bundle_info_service =\Drupal::service('entity_type.bundle.info');
      $bundles_info = $bundle_info_service->getBundleInfo($entity_type);

      $storage = $this->entityTypeManager->getStorage($entity_type);
      $ids = $storage->getQuery()
        ->condition($bundle_key, $bundles_type, 'IN')
        ->sort($label_key, 'ASC', $langcode)
        ->execute();

      $entities_list = $storage->loadMultiple($ids);

      foreach($entities_list as $entity) {
        $bundle_machine_name = $entity->bundle();
        $list[$bundles_info[$bundle_machine_name]['label']][$entity->id()] = $entity->label();
        if (!empty($this->labelSubstitution) && isset($entity->{$this->labelSubstitution}) && !empty($entity->{$this->labelSubstitution}->value)) {
          $list[$bundles_info[$bundle_machine_name]['label']][$entity->id()] = $entity->{$this->labelSubstitution}->value;
        }
      }
      $this->cacheList[$cache_key] = $list;
    }
    return $this->cacheList[$cache_key];
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    if (!\Drupal::currentUser()->hasPermission('edit in place field editing permission')) {
      return parent::viewElements($items, $langcode);
    }
    $elements = [];
    $choice_list = [];
    $cardinality = 1;
    $entity_type = '';

    if (!empty($items) ) {
      // Get entity type (node, taxonomy_term...).
      $entity_type = $items->getFieldDefinition()->getSetting('target_type');

      // Get cardinality of current field.
      $cardinality = $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality();

      // Get target entity properties fields names.
      $bundle_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('bundle');
      $label_key = $this->entityTypeManager->getDefinition($entity_type)->getKey('label');

      $handle_settings = $items->getFieldDefinition()->getSetting('handler_settings');
      if (!empty($handle_settings)) {

        // Get possible choices from selected bundles.
        if (isset($handle_settings['target_bundles'])) {

          // Get possible bundles for list.
          $bundles_type = $handle_settings['target_bundles'];
          $choice_list = $this->getSelectableEntitiesList($entity_type, $bundle_key, $bundles_type, $label_key, $langcode);
          if (count($choice_list) === 1) {
            $choice_list = current($choice_list);
          }
        }

        // Get possible choices from a View.
        if (isset($handle_settings['view'])){
          $view = Views::getView($handle_settings['view']['view_name']);
          if (is_object($view)) {
            $view->setDisplay($handle_settings['view']['display_name']);
            $view->preExecute();
            $view->execute();
            foreach($view->result as $row) {
              $choice_list[$row->_entity->id()] = $row->_entity->label();
              if (!empty($this->labelSubstitution) && isset($row->_entity->{$this->labelSubstitution}) && !empty($row->_entity->{$this->labelSubstitution}->value)) {
                $choice_list[$row->_entity->id()] = $row->_entity->{$this->labelSubstitution}->value;
              }
            }
          }
        }
      }


    }
    $selected = [];

    /**  @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
    foreach ($items as $delta => $item) {
      $selected[] = $item->target_id;
    }

    $labels = [];
    $entities = [];
    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $labels[$delta] = $entity->label();
      if (!empty($this->labelSubstitution) && isset($entity->{$this->labelSubstitution}) && !empty($entity->{$this->labelSubstitution}->value)) {
        $labels[$delta] = $entity->{$this->labelSubstitution}->value;
      }
      $entities[$delta] = $entity;
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
          '#theme' => 'edit_in_place_reference_label',
          '#labels' => $labels,
          '#entities' => $entities,
          '#entity_type' => $entity_type,
          '#field_name' => $items->getName(),
          '#entity_id' => $items->getEntity()->id(),
          '#lang_code' => $items->getEntity()->language()->getId(),
        ],
        'form_container' =>  $this->formBuilder->getForm('Drupal\edit_in_place_field\Form\EditInPlaceFieldReferenceForm', [
          'choice_list' => $choice_list,
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
    $options['label_substitution'] = '';
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['label_substitution'] = [
      '#type' => 'textfield',
      '#title' =>  $this->t('Label substitution'),
      '#description' =>  $this->t('Specify a field that will replace the label at display.'),
      '#default_value' => $this->getSetting('label_substitution'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $label_substitution = $this->getSetting('reference_parent_field_name');
    if ($label_substitution) {
      $summary[] = $this->t('Label replace: @label_substitution', [
        '@label_substitution' => $label_substitution,
      ]);
    }
    return $summary;
  }
}
