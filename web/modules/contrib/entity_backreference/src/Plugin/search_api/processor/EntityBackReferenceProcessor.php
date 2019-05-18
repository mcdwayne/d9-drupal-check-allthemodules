<?php

namespace Drupal\entity_backreference\Plugin\search_api\processor;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\field\Entity\FieldConfig;
use Drupal\search_api\Processor\ProcessorProperty;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @SearchApiProcessor(
 *   id = "entity_backreference_index",
 *   label = @Translation("Entity Backreference indexing"),
 *   description = @Translation("Entity Backreference index fields"),
 *   stages = {
 *     "add_properties" = 1,
 *     "pre_index_save" = -10,
 *     "preprocess_index" = -30
 *   }
 * )
 */
class EntityBackReferenceProcessor extends ProcessorPluginBase implements PluginFormInterface, ContainerFactoryPluginInterface {

  /**
   * Entity field manager service.
   *
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Entity type manager service.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'entity_backreference' => array(),
    );
  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $options = [];

    if (isset($this->configuration['entity_backreference'])) {
      $default_settings = $this->configuration['entity_backreference'];
    }
    foreach($this->index->getDatasources() as $datasource){
      $bundles = $datasource->getBundles();
      foreach($bundles as $bundle_id => $bundle){
        $fields = $this->referencingFields($datasource->getEntityTypeId(), $bundle_id, $this->entityFieldManager);
        foreach($fields as $field){
          $options[$field->id()] = $field->getLabel() . ' (' . $field->id() .  ')';
        }
      }
    }

    $form['entity_backreference'] = array(
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable these backreference fields on this index'),
      '#description' => $this->t('This will index IDs from backreference content'),
      '#options' => $options,
      '#default_value' => $default_settings,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    $fields = array_filter($form_state->getValues()['entity_backreference']);
    if ($fields) {
      $fields = array_keys($fields);
    }
    $form_state->setValue('entity_backreference', $fields);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->setConfiguration($form_state->getValues());
  }


  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = array();

    if (!$datasource) {
      // Ensure that our fields are defined.
      $fields = $this->getFieldsDefinition();

      foreach ($fields as $field_id => $field_definition) {
        $field_id = str_replace('.','_',$field_id);
        $properties[$field_id] = new ProcessorProperty($field_definition);
      }
    }
    return $properties;
  }

  /**
   * Helper function for defining our custom fields.
   */
  protected function getFieldsDefinition() {
    $fields = [];
    $config = $this->configuration['entity_backreference'];
    foreach($config as $field){
      $field_array = explode('.',$field);
      $fieldconfig = $this->entityTypeManager->getStorage('field_config')->load($field_array[0] . '.' . $field_array[1] . '.' .  $field_array[2]);
      $fields[$field] = array(
        'label' => $fieldconfig->getLabel(),
        'description' => $this->t('This is entity reference field on this bundle.'),
        'type' => 'integer',
        'processor_id'=> $this->getPluginId()
      );
    }
    return $fields;
  }


  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array $items) {
    $config = $this->configuration['entity_backreference'];
    foreach ($items as $item) {
      foreach($config as $fields){
        $original_entity = $item->getOriginalObject()->getValue();
        $fields_config = explode('.',$fields);
        $field = str_replace('.','_',$fields);
        $query = \Drupal::entityQuery($fields_config[0]);
        $query->condition('type',$fields_config[1]);
        $query->condition($fields_config[2],$original_entity->id());
        $results = $query->execute();
        foreach($results as $result){
          foreach($this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, $field) as $nid_based_field){
            $nid_based_field->addValue($result);
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preIndexSave() {
    foreach ($this->getFieldsDefinition() as $field_id => $field_definition) {
      $field_id = str_replace('.','_',$field_id);
      $this->ensureField(NULL, $field_id, $field_definition['type']);
    }
  }

  /**
   * Check if field applies to given entity
   *
   * @param  FieldConfig $field
   * @param  string      $entity_type_id
   * @param  string      $entity_bundle_id
   * @return bool        TRUE is applies, FALSE if not
   */
  public function referenceFieldAppliesToEntity(FieldConfig $field, $entity_type, $bundle = NULL) {
    $entity_type_targeted_by_field = $field->getSetting('target_type');
     $field_handler = $field->getSetting('handler_settings');
     return $entity_type_targeted_by_field == $entity_type &&
       (!isset($field_handler['target_bundles']) || (isset($field_handler['target_bundles']) &&
       isset($field_handler['target_bundles'][$bundle] )));
  }
  /**
   * Return referencing fields
   *
   * @param $entity_type_id
   * @param $entity_bundle_id
   *
   * @return array
   */
  public function referencingFields($entity_type_id, $entity_bundle_id) {
    $entity_reference_fields = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');
    $fields = [];
    foreach ($entity_reference_fields as $entity_type_field_is_on => $field_info) {
      foreach ($field_info as $field_name => $field_data) {
        foreach ($field_data['bundles'] as $entity_bundle_field_is_on) {
          /* @var \Drupal\field\Entity\FieldConfig */
          $field = $this->entityTypeManager->getStorage('field_config')->load($entity_type_field_is_on . '.' . $entity_bundle_field_is_on . '.' . $field_name);
          if ($field && $this->referenceFieldAppliesToEntity($field, $entity_type_id, $entity_bundle_id)) {
            $fields[$field_name] = $field;
          }
        }
      }
    }
    return $fields;
  }
}
