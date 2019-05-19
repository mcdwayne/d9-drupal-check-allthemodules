<?php

/**
 * @file
 * Contains \Drupal\temporal\Form\TemporalTypeForm.
 */

namespace Drupal\temporal\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\temporal\Entity\TemporalType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TemporalTypeForm.
 *
 * @package Drupal\temporal\Form
 */
class TemporalTypeForm extends EntityForm {
  /**
   * The name of the entity type.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity bundle.
   *
   * @var string
   */
  protected $bundle;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   * The query factory to create entity queries.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  public $queryFactory;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new FieldStorageAddForm object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_plugin_manager
   *   The field type plugin manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $query_factory
   *   The entity query factory.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, FieldTypePluginManagerInterface $field_type_plugin_manager, QueryFactory $query_factory, ConfigFactoryInterface $config_factory) {
    $this->entityManager = $entity_manager;
    $this->fieldTypePluginManager = $field_type_plugin_manager;
    $this->queryFactory = $query_factory;
    $this->configFactory = $config_factory;
  }
  
  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('entity.query'),
      $container->get('config.factory')
    );
  }
  
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    /** @var TemporalType $temporal_type */
    $temporal_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $temporal_type->label(),
      '#description' => $this->t("Label for the Temporal type."),
      '#required' => TRUE,
    );

    $form['description'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Description'),
      '#maxlength' => 255,
      '#default_value' => $temporal_type->description,
      '#description' => $this->t("Description of the Temporal type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $temporal_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\temporal\Entity\TemporalType::load',
      ),
      '#disabled' => !$temporal_type->isNew(),
    );

    $existing_field_storage_options = $this->getExistingFieldStorageOptions();

    $form['field_to_track'] = array(
      '#type' => 'select',
      '#title' => $this->t('Field to track'),
      '#maxlength' => 255,
      '#options' => $existing_field_storage_options,
      '#default_value' => $temporal_type->getFieldToTrack(),
      '#description' => $this->t("Field to monitor for changes"),
      '#required' => TRUE,
    );

    $form['tracking_type'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Tracking Type'),
      '#options' => ['historical' => 'Historical', 'audit' => 'Audit'],
      '#default_value' => $temporal_type->getTrackingType(),
      '#required' => true,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $temporal_type = $this->entity;
    $status = $temporal_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Temporal type.', [
          '%label' => $temporal_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Temporal type.', [
          '%label' => $temporal_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($temporal_type->urlInfo('collection'));
  }

  /**
   * Returns an array of existing field storages that can be added to a bundle.
   *
   * @return array
   *   An array of existing field storages using a compound key.
   *
   * Key is comprised of:
   * entity__bundle__field_type__field_name
   *
   * This allows the full representation of data needed to track a field
   */
  protected function getExistingFieldStorageOptions() {
    $options = array();
    $field_type = '';

    // List of field types that we will track
    $include = temporal_field_type_mapping();

    // List of entity types for discovering fields to monitor
    $entity_types = temporal_entity_types();

    // Additional entity properties we want to track
    $entity_type_properties = temporal_entity_properties();

    $option_entities = [];
    // Load the field_storages and build the list of options.
    $field_types = $this->fieldTypePluginManager->getDefinitions();

    foreach ($entity_types AS $entity_type) {
      $entity_definitions = $this->entityManager->getFieldStorageDefinitions($entity_type);
      // Build the node fields
      foreach ($entity_definitions as $field_name => $field_storage) {
        $field_type = $field_storage->getType();
        // Verify that the field type is one we want to track
        if (!isset($include[$field_type])) {
          continue;
        }
        // Do not show:
        // - non-configurable field storages,
        // - locked field storages,
        // - field storages that should not be added via user interface,
        // - field storages that already have a field in the bundle.
        if ($field_storage instanceof FieldStorageConfigInterface
          && !$field_storage->isLocked()
          && empty($field_types[$field_type]['no_ui'])
          && !in_array('temporal_type', $field_storage->getBundles(), TRUE)
        ) {
          foreach ($field_storage->getBundles() as $bundle_name => $bundle) {
            $option_key = $entity_type . '__' . $bundle_name . '__' . $field_type . '__' . $field_name;
            $options[$bundle_name][$option_key] = $this->t('@type: @field', array(
              '@type' => $field_types[$field_type]['label'],
              '@field' => $field_name,
            ));
            $option_entities[$entity_type][$bundle_name] = TRUE;
          }
        }
      }
    }
    // Add the properties we want to track to entity option lists
    foreach ($entity_type_properties as $entity_type => $properties) {
      foreach($options AS $bundle_name => $option_values) {
        foreach($properties AS $property_name => $property_type)
          // Add the entity type properties we want to track
          if (isset($option_entities[$entity_type][$bundle_name])) {
            $options[$bundle_name][$entity_type . '__'. $bundle_name .'__' . $property_type . '__' . $property_name] = $this->t('@type: @field', array(
              '@type' => $field_types[$property_type]['label'],
              '@field' => $property_name . ' (property)',
            ));
          }
      }
    }
    return $options;
  }
}
