<?php

namespace Drupal\migrate_gathercontent\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\migrate_gathercontent\DrupalGatherContentClient;
use Drupal\field\Entity\FieldConfig;

/**
 * Form handler for the Example add and edit forms.
 */
class MappingEditForm extends EntityForm {


  /**
   * Entity Type Manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Entity Field Manger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityFieldManager;

  /**
   * Drupal GatherContent Client.
   *
   * @var \drupal\migrate_gathercontent\drupalgathercontentclient
   */
  protected $client;

  /**
   * Migration Plugin Manager
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs an MappingEditForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *    The entityTypeManager service.
   * @param \Drupal\Core\Entity\EntityFieldManager $entityFieldManager
   *    The entityFieldManager service.
   * @param \Drupal\migrate_gathercontent\DrupalGatherContentClient $gathercontent_client
   *    The Drupal GatherContent client service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *    The Migraton Plugin Manager service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager, DrupalGatherContentClient $gathercontent_client, MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->client = $gathercontent_client;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('migrate_gathercontent.client'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;

    // Getting list of field options for this entity.
    $fields = $this->getWritableFields($entity);
    $field_options = [];
    foreach($fields as $id => $field) {
      if ($field instanceof FieldConfig) {
        $field_options['Fields'][$id] = $field->getLabel();
      }
      else {
        $field_options['Properties'][$id] = $field->getlabel();
      }
    }

    $entity_field_select = [
      '#type' => 'select',
      '#options' => $field_options,
      '#empty_option' => $this->t('- Do not map -'),
      '#option_value' => NULL,
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $entity->label(),
      '#description' => $this->t("Label for this mapping."),
      '#required' => TRUE,
    ];
    $form['mapping_id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$entity->isNew(),
    ];

    // Getting list of groups
    $groups = \Drupal::entityTypeManager()->getStorage('gathercontent_group')->loadMultiple();
    $group_options = [];
    foreach($groups as $id => $group) {
      $group_options[$id] = $group->label();
    }

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#description' => $this->t('Mappings that are not enabled will be excluded from the migration.'),
      '#default_value' => $entity->get('status'),
    ];

    $form['group_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#required' => TRUE,
      '#description' => $this->t('Choose the Group for this mapping.'),
      '#default_value' => $entity->get('group_id'),
      '#options' => $group_options,
    ];

    // TODO: Load all the fields in the GatherContent template and entity bundle.
    // Getting the bundle fields.
    $template = $this->client->templateGet($this->entity->get('template'));
    $mappings = $this->entity->getFieldMappings();

    // Default field mappings (e.g for Name and status.)
    // These are fields that exist in gathercontent and are not configurable
    // by the user.
    $default_fields = [
      'name' => $this->t('Name'),
      'status' => $this->t('Status'),
    ];

    // TODO: Refactor this. You are duplicating logic.
    $form['default'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Default'),
    ];
    $form['default']['field_mappings'] = [
      '#type' => 'table',
      '#header' => [
        ['data' => ['#markup' => $this->t('Source')], 'width' => '50%'],
        ['data' => ['#markup' => $this->t('Field')], 'width' => '50%'],
      ],
    ];

    foreach ($default_fields as $name => $label) {
      $entity_field_select['#default_value'] = (!empty($mappings[$name]['field'])) ? $mappings[$name]['field'] : '';
      $form['default']['field_mappings'][$name] = [
        'label' => [
          '#markup' => $label,
        ],
        'entity_field' => $entity_field_select,
      ];
    }

    // Create grouped table of fields.
    foreach ($template->config as $tab_id => $tab) {

      $form[$tab_id] = [
        '#type' => 'details',
        '#title' => $tab->label,
        '#open' => TRUE,
      ];

      $form[$tab_id]['field_mappings'] = [
        '#type' => 'table',
        '#header' => [
          ['data' => ['#markup' => $this->t('Source')], 'width' => '50%'],
          ['data' => ['#markup' => $this->t('Field')], 'width' => '50%'],
        ],
      ];

      // TODO: Need to create a field mapping edit form. Like field settings.
      foreach ($tab->elements as $field) {
        $entity_field_select['#default_value'] = (!empty($mappings[$field->id]['field'])) ? $mappings[$field->id]['field'] : '';
        $form[$tab_id]['field_mappings'][$field->id] = [
          'label' => [
            '#markup' => $field->label . ' (' . $field->type .')',
          ],
          'entity_field' => $entity_field_select,
        ];
      }
    }

    // Loading all mapping entities.
    // This is for setting migration dependencies.

    // First get all mappings that are already saved.
    // We do this to preserve the weight order.
    // Note: Any mappings that aren't saved will always have a weight of 0.
    $mapping_ids = [];
    if (!empty($mappings)) {
      foreach ($mappings as $id => $field) {
        // Load the migration.
        $mapping_ids[] = $id;
      }
    }
    // Add the rest of available mappings.
    // Only load mappings that are part of this group.
    $mapping_entities = $this->entityTypeManager->getStorage('gathercontent_mapping')->loadByProperties([
      'group_id' => $entity->get('group_id')
    ]);
    if (!empty($mapping_entities)) {
      foreach ($mapping_entities as $mapping_entity) {
        if ($this->entity->id() != $mapping_entity->id() && !in_array($mapping_entity->id(), $mapping_ids)) {
          $mapping_ids[] = $mapping_entity->id();
        }
      }
    }
    $mapping_entities = $this->entityTypeManager->getStorage('gathercontent_mapping')->loadMultiple($mapping_ids);
    if (!empty($mapping_entities)) {

      $form['mapping_migrations'] = [
        '#title' => $this->t('Migration Dependencies'),
        '#type' => 'details',
        '#open' => TRUE,
        '#description' => $this->t("Add other migrations as dependencies. You can also optionally map those migrations to fields on this entity. This is useful for entity reference fields like media, paragraphs etc.")
      ];
      $form['mapping_migrations']['migration_mappings'] = [
        '#type' => 'table',
        '#header' => [
          ['data' => ['#markup' => $this->t('Migration')], 'width' => '50%'],
          ['data' => ['#markup' => $this->t('Field')], 'width' => '50%'],
          'weight' => '',
        ],
        '#tabledrag' => [
          [
            'action' => 'order',
            'relationship' => 'sibling',
            'group' => 'migrations-order-weight',
          ]
        ],
      ];

      // TODO: This should be handled with plugins some way.
      $field_options = [];
      $fields = $this->getWritableFields($entity, [
        'entity_reference',
        'entity_reference_revisions'
      ]);
      foreach ($fields as $id => $field) {
        $field_options['Fields'][$id] = $field->getLabel();
      }

      foreach ($mapping_entities as $migration) {
        $id = $migration->id();

        // Getting the weight of this migration.
        $weight = (isset($mappings[$id]['weight'])) ? $mappings[$id]['weight'] : 0;

        $form['mapping_migrations']['migration_mappings'][$id] = [
          // Making this table draggable.
          '#attributes' => [
            'class' => ['draggable'],
          ],
          '#weight' => $weight,
          'import' => [
            '#type' => 'checkbox',
            '#title' => ($migration->isEnabled()) ? $migration->label() : $migration->label() . ' ' . $this->t('(disabled)'),
            '#default_value' => (isset($mappings[$id]['import'])) ? $mappings[$id]['import'] : FALSE,
          ],
          'entity_field' => [
            '#type' => 'select',
            '#options' => $field_options,
            '#empty_option' => $this->t('- Do not map -'),
            '#option_value' => NULL,
            '#default_value' => (isset($mappings[$id]['field'])) ? $mappings[$id]['field'] : FALSE,
            '#states' => [
              'disabled' => [
                ':input[name="migration_mappings[' . $id .'][import]"]' => ['checked' => FALSE],
              ]
            ]
          ],
          'weight' => [
            '#type' => 'weight',
            '#title' => $this->t('Weight'),
            '#title_display' => 'invisible',
            '#default_value' => $weight,
            '#attributes' => ['class' => ['migrations-order-weight']]
          ],
        ];
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Helper function for fetching writeable fields.
   * @param $entity
   * @param array $field_types
   * @return array
   */
  private function getWritableFields($entity, $field_types = []) {

    // Getting list of field options for this entity.
    $fields = $this->entityFieldManager->getFieldDefinitions($entity->get('entity_type'), $entity->get('bundle'));
    $writeable_fields = [];
    foreach($fields as $id => $field) {
      // Only map writable fields.
      // TODO: Some writable fields we probably don't want to write to.
      // e.g 'default revision'
      if (!$field->isReadOnly()) {
        $type = $field->getType();
        if (empty($field_types)) {
          $writeable_fields[$id] = $field;
        }
        else {
          if (in_array($type, $field_types)) {
            $writeable_fields[$id] = $field;
          }
        }
      }
    }

    return $writeable_fields;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $mappings = [];

    // Saving field mappings.
    $field_mappings = $form_state->getValue('field_mappings');
    foreach ($field_mappings as $gc_field_id => $field) {
      if (!empty($field['entity_field'])) {
        $mappings[$gc_field_id] = [
          'weight' => 0,
          'field' => $field['entity_field']
        ];
      }
    }

    // Saving migration mappings.
    $migration_mappings = $form_state->getValue('migration_mappings');
    if (!empty($migration_mappings)) {
      foreach ($migration_mappings as $migration_id => $field) {
        $value = [];
        if (!empty($field['import'])) {
          $value += [
            'import' => TRUE
          ];
          if (!empty($field['entity_field'])) {
            $value += [
              'field' => $field['entity_field']
            ];
          }
        }
        if (!empty($value)) {
          $value += [
            'weight' => (int) $field['weight']
          ];
          $mappings[$migration_id] = $value;
        }
      }
    }

    $this->entity->set('field_mappings', $mappings);

    if ($this->entity->save()) {

      // Invalidating the cache so that it gets rebuilt after saving the entity.
      $this->migrationPluginManager->clearCachedDefinitions();

      $params = [
        'group_id' => $form_state->getValue('group_id'),
      ];
      $form_state->setRedirect('migrate_gathercontent.mapping.collection', $params);
    }
  }

  /**
   * Helper function to check whether an Example configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('gathercontent_mapping')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

}