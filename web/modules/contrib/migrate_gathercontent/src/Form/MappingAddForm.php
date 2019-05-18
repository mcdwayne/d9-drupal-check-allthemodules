<?php
namespace Drupal\migrate_gathercontent\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\MigrateSourcePluginManager;
use Drupal\migrate_gathercontent\DrupalGatherContentClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\ContentEntityType;

/**
 * Form handler for the Example add and edit forms.
 */
class MappingAddForm extends EntityForm {

  /**
   * Drupal GatherContent Client.
   * @var \drupal\migrate_gathercontent\drupalGatherContentClient
   */
  protected $client;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Migration Plugin Manager
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs an ExampleForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, DrupalGatherContentClient $gathercontent_client, MigrationPluginManagerInterface $migration_plugin_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->client = $gathercontent_client;
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('migrate_gathercontent.client'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * Helper function for list of supported entity types.
   *
   * @return array
   */
  private function entityTypeOptions() {
    $entity_definitions = $this->entityTypeManager->getDefinitions();
    $entity_types = [];

    foreach ($entity_definitions as $id => $entity_type) {
      if ($entity_type instanceof ContentEntityType) {
        $entity_types[$id] = $entity_type->getLabel();
      }
    }

    return $entity_types;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $entity = $this->entity;

    $form['#prefix'] = '<div id="mapping-add-form">';
    $form['#suffix'] = '</div>';

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

    $form['group_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#required' => TRUE,
      '#description' => $this->t('Choose the Group for this mapping.'),
      '#default_value' => $entity->get('group_id'),
      '#options' => $group_options,
    ];

    // Getting supported gather content projects.
    $selected_templates = \Drupal::config('migrate_gathercontent.settings')->get('projects');
    foreach ($selected_templates as $project_id) {
      // Set active project.
      if (empty($active_project)) {
        $active_project = $project_id;
      }
      $project = $this->client->projectGet($project_id);
      $options[$project_id] = $project->name;
    }

    $form['project_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Project'),
      '#description' => $this->t('Choose the GatherContent project'),
      '#default_value' => $entity->get('project_id'),
      '#options' => $options,
      '#ajax' => [
        'callback' => 'Drupal\migrate_gathercontent\Form\MappingAddForm::rebuildForm',
        'event' => 'change',
        'wrapper' => 'mapping-add-form',
      ]
    ];

    // Get source plugin manager.
    if ($form_state->getValue('project_id')) {
      $active_project = $form_state->getValue('project_id');
    }
    $options = [];
    if ($active_project && !empty($selected_templates)) {
      // Loading all the templates in the project.
      $templates = $this->client->templatesGet($active_project);
      foreach ($templates as $template_id => $template) {
        $options[$template_id] = $template->name . ' (' . $template->usage->itemCount . ' items)';
      }
    }

    $form['template'] = [
      '#type' => 'select',
      '#title' => $this->t('Template'),
      '#description' => $this->t('Choose the GatherContent template'),
      '#default_value' => $entity->get('template'),
      '#options' => $options,
    ];

    // This should only list content entity types not config.
    // Supporting nodes and taxonomy for now.
    if ($form_state->getValue('entity_type')) {
      $entity_type = $form_state->getValue('entity_type');
    }
    else {
      $entity_type = 'node';
    }

    // The content entity type.
    $form['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('Choose the entity type.'),
      '#default_value' => $entity_type,
      '#options' => $this->entityTypeOptions(),
      '#ajax' => [
        'callback' => 'Drupal\migrate_gathercontent\Form\MappingAddForm::rebuildForm',
        'event' => 'change',
        'wrapper' => 'mapping-add-form',
      ]
    ];

    $bundle_entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type)->getBundleEntityType();
    $bundles = \Drupal::entityTypeManager()->getStorage($bundle_entity_type)->loadMultiple();
    $bundle_options = [];
    foreach($bundles as $bid => $bundle) {
      $bundle_options[$bid] = $bundle->label();
    }

    $form['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Bundle'),
      '#options' => $bundle_options,
      '#default_value' => $entity->get('bundle'),
      '#description' => $this->t('Choose the bundle you want to map to'),
    ];

    // TODO: Update the save button to say "Save and manage mapping."
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Set entity to enabled by default.
    $this->entity->set('status', 1);

    if ($this->entity->save()) {

      // Invalidating the cache so that it gets rebuilt after saving the entity.
      $this->migrationPluginManager->clearCachedDefinitions();

      $params = [
        'gathercontent_mapping' => $form_state->getValue('mapping_id'),
      ];
      $form_state->setRedirect('entity.gathercontent_mapping.edit_form', $params);
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

  /**
   * Form callback function for rebuilding the form.
   */
  public static function rebuildForm(array &$form, FormStateInterface $form_state) {

    $form_state->setRebuild();

    return $form;
  }

}