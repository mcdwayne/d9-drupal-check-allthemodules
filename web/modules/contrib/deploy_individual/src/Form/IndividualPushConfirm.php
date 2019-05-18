<?php

namespace Drupal\deploy_individual\Form;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\multiversion\MultiversionManagerInterface;
use Drupal\multiversion\Workspace\WorkspaceManagerInterface;
use Drupal\replication\Entity\ReplicationLogInterface;
use Drupal\replication\ReplicationTask\ReplicationTask;
use Drupal\user\PrivateTempStoreFactory;
use Drupal\user\UserStorageInterface;
use Drupal\workspace\Entity\Replication;
use Drupal\workspace\ReplicatorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a confirmation form for deploying content.
 */
class IndividualPushConfirm extends ConfirmFormBase {

  /**
   * The depth to search for referenced entities.
   */
  const MAX_REFERENCED_ENTITIES_DEPTH = 5;

  /**
   * The separator to create option values.
   */
  const VALUE_SEPARATOR = '___';

  /**
   * The temp store factory.
   *
   * @var \Drupal\user\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The user storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The replicator manager.
   *
   * @var \Drupal\workspace\ReplicatorInterface
   */
  protected $replicatorManager;

  /**
   * The workspace manager.
   *
   * @var \Drupal\multiversion\Workspace\WorkspaceManagerInterface
   */
  protected $workspaceManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The multiversion manager.
   *
   * @var \Drupal\multiversion\MultiversionManagerInterface
   */
  protected $multiversionManager;

  /**
   * The source workspace pointer.
   *
   * @var \Drupal\workspace\WorkspacePointerInterface
   */
  protected $source = NULL;

  /**
   * The target workspace pointer.
   *
   * @var \Drupal\workspace\WorkspacePointerInterface
   */
  protected $target = NULL;

  /**
   * The replicable entity types.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface[]
   */
  protected $replicableEntities = array();

  /**
   * Constructs a new IndividualPushConfirm.
   *
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The temp store factory.
   * @param \Drupal\user\UserStorageInterface $user_storage
   *   The user storage.
   * @param \Drupal\workspace\ReplicatorInterface $replicator_manager
   *   The conflict tracker.
   * @param \Drupal\multiversion\Workspace\WorkspaceManagerInterface $workspace_manager
   *   The conflict tracker.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\multiversion\MultiversionManagerInterface $multiversion_manager
   *   The multiversion manager.
   */
  public function __construct(
    PrivateTempStoreFactory $temp_store_factory,
    UserStorageInterface $user_storage,
    ReplicatorInterface $replicator_manager,
    WorkspaceManagerInterface $workspace_manager,
    EntityTypeManagerInterface $entity_type_manager,
    MultiversionManagerInterface $multiversion_manager
  ) {
    $this->tempStoreFactory = $temp_store_factory;
    $this->userStorage = $user_storage;
    $this->replicatorManager = $replicator_manager;
    $this->workspaceManager = $workspace_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->multiversionManager = $multiversion_manager;
    $this->replicableEntities = $multiversion_manager->getSupportedEntityTypes();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('workspace.replicator_manager'),
      $container->get('workspace.manager'),
      $container->get('entity_type.manager'),
      $container->get('multiversion.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'individual_push_confirm';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to deploy these entities?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('<front>');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Deploy entities');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Retrieve the entities to be deployed from the temp store.
    /** @var \Drupal\Core\Entity\EntityInterface[]|NULL $entities */
    $entities = $this->tempStoreFactory
      ->get('deploy_individual_push_individual')
      ->get($this->currentUser()->id());

    if (!$entities) {
      return $this->redirect('<front>');
    }

    // Table to select entities.
    $header = [
      'label' => $this->t('Label'),
      'type' => $this->t('Type'),
      'bundle' => $this->t('Bundle'),
    ];

    $options = array();
    $default_value = array();
    foreach ($entities as $entity) {
      $this->buildEntityOptions($options, $entity);

      $option_value = $this->formatOptionValue($entity, '');
      $default_value[$option_value] = $option_value;
    }

    $form['entities'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $default_value,
      '#empty' => $this->t('No entities to be pushed have been found.'),
    );

    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $selected_entities = $form_state->getValue('entities');
    $selected_entities = array_filter($selected_entities);
    if (empty($selected_entities)) {
      $form_state->setErrorByName('entities', $this->t('You must select at least one entity.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Clear out the accounts from the temp store.
    $current_user_id = $this->currentUser()->id();
    $this->tempStoreFactory->get('deploy_individual_push_individual')->delete($current_user_id);

    if ($form_state->getValue('confirm')) {
      // Deploy selected entities.
      $selected_entities = $form_state->getValue('entities');
      $selected_entities = array_filter($selected_entities);

      $organized_uuids = $this->extractUuids($selected_entities);
      // Reload entities to put their label in the description of the
      // Replication.
      $prepared_entities = [];
      foreach ($organized_uuids as $entity_type => $uuids) {
        $entities = $this->entityTypeManager
          ->getStorage($entity_type)
          ->loadByProperties(array('uuid' => $uuids));

        foreach ($entities as $entity) {
          $prepared_entities[] = $entity;
        }
      }

      $this->deploy($prepared_entities);
    }
  }

  /**
   * Helper function to deploy individual content.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   An array of entities.
   */
  protected function deploy($entities) {
    $task = new ReplicationTask();
    $uuids = array();
    // Limit name length to the max in the entity base fields definition (50).
    $name = Unicode::truncate($this->t('Deploy @source to @target', [
      '@source' => $this->getDefaultSource()->label(),
      '@target' => $this->getDefaultTarget()->label(),
    ])->render(), 50);
    $description = $this->formatPlural(
      count($entities),
      'Deployment of the following entity:',
      'Deployment of the following entities:'
    )
      ->render();
    $description .= PHP_EOL;

    foreach ($entities as $entity) {
      $uuids[] = $entity->uuid();
      $description .= $entity->label() . PHP_EOL;
    }
    $task->setParameter('uuids', $uuids);

    $replication = Replication::create(array(
      'name' => $name,
      'description' => $description,
      'source' => $this->getDefaultSource()->id(),
      'target' => $this->getDefaultTarget()->id(),
    ));

    try {
      $response = $this->replicatorManager->replicate(
        $this->getDefaultSource(),
        $this->getDefaultTarget(),
        $task
      );

      if (($response instanceof ReplicationLogInterface) && ($response->get('ok')->value == TRUE)) {
        $replication->set('replicated', REQUEST_TIME)
          ->save();
        drupal_set_message($this->t('Successful deployment.'));
      }
      else {
        drupal_set_message($this->t('Deployment error. Check recent log messages for more details.'), 'error');
      }
    }
    catch (\Exception $e) {
      watchdog_exception('Deploy individual', $e);
      drupal_set_message($e->getMessage(), 'error');
    }
  }

  /**
   * Helper function to return the default source.
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\workspace\WorkspacePointerInterface|mixed
   *   A WorkspacePointer to the source.
   */
  protected function getDefaultSource() {
    if (!empty($this->source)) {
      return $this->source;
    }

    /** @var \Drupal\multiversion\Entity\Workspace $workspace */
    $workspace = $this->workspaceManager->getActiveWorkspace();
    $workspace_pointers = $this->entityTypeManager
      ->getStorage('workspace_pointer')
      ->loadByProperties(['workspace_pointer' => $workspace->id()]);
    return $this->source = reset($workspace_pointers);
  }

  /**
   * Helper function to return the default target.
   *
   * @return \Drupal\workspace\WorkspacePointerInterface
   *   A WorkspacePointer to the target.
   */
  protected function getDefaultTarget() {
    if (!empty($this->target)) {
      return $this->target;
    }

    /** @var \Drupal\multiversion\Entity\Workspace $workspace */
    $workspace = $this->workspaceManager->getActiveWorkspace();
    return $this->target = $workspace->get('upstream')->entity;
  }

  /**
   * Helper function to check if an entity is replicable.
   *
   * @param EntityInterface $entity
   *   The entity to check the replicability.
   *
   * @return bool
   *   TRUE if the entity is replicable. FALSE otherwise
   */
  protected function entityReplicable(EntityInterface $entity) {
    foreach ($this->replicableEntities as $replicable_entity) {
      $supported_entity_class = $replicable_entity->getOriginalClass();
      if ($entity instanceof $supported_entity_class) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Helper function to get replicable entities referenced from an entity.
   *
   * @param EntityInterface $entity
   *   The entity to get the replicable referenced entities from.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of replicable referenced entities.
   */
  protected function getReplicableReferencedEntities(EntityInterface $entity) {
    $replicable_referenced_entities = array();
    $referenced_entities = $entity->referencedEntities();
    foreach ($referenced_entities as $referenced_entity) {
      if (($referenced_entity instanceof ContentEntityInterface) && $this->entityReplicable($referenced_entity)) {
        $replicable_referenced_entities[] = $referenced_entity;
      }
    }
    return $replicable_referenced_entities;
  }

  /**
   * Extract entity UUID from string.
   *
   * Expected entry format: entity_type_id___(uuids___...)entity_uuid.
   *
   * @param string[] $selected_entities
   *   The selected entities UUID as string.
   *
   * @return string[]
   *   The selected UUID string.
   */
  protected function extractUuids($selected_entities) {
    $cleaned_selected_entities = array();

    foreach ($selected_entities as $selected_entity) {
      $uuids = explode($this::VALUE_SEPARATOR, $selected_entity);
      $type = $uuids[0];
      $uuid = end($uuids);

      if (!isset($cleaned_selected_entities[$type])) {
        $cleaned_selected_entities[$type] = [];
      }

      $cleaned_selected_entities[$type][$uuid] = $uuid;
    }

    return $cleaned_selected_entities;
  }

  /**
   * Helper function to format the label of an entity for the option.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to format the label.
   * @param int $level
   *   The number of indentation div.
   *
   * @return \Drupal\Component\Render\FormattableMarkup
   *   A formatted label.
   */
  protected function formatOptionLabel(EntityInterface $entity, $level = 0) {
    $indentation = '';
    for ($i = 1; $i <= $level; $i++) {
      $indentation .= '<div class="indentation">&nbsp;</div>';
    }

    return new FormattableMarkup($indentation . '@label', array(
      '@label' => $this->t('@entity_label (id: @entity_id)', array(
        '@entity_label' => $entity->label(),
        '@entity_id' => $entity->id(),
      )),
    ));
  }

  /**
   * Helper function to format the value of an entity for the option.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to format the value.
   * @param string $referencing_uuids
   *   The referencing uuids. Format: uuid1___uuid2___...
   *
   * @return string
   *   A formatted value.
   */
  protected function formatOptionValue(EntityInterface $entity, $referencing_uuids = '') {
    return $entity->getEntityTypeId() . $this::VALUE_SEPARATOR . $referencing_uuids . $entity->uuid();
  }

  /**
   * Helper function to generate option for an entity.
   *
   * @param array $options
   *   The array of options for the tableselect form type element.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to create the option on.
   * @param string $referencing_uuids
   *   The referencing uuids. Format: uuid1___uuid2___...
   * @param int $level
   *   The number of indentation div.
   */
  protected function addEntityOption(array &$options, EntityInterface $entity, $referencing_uuids = '', $level = 0) {
    $options[$this->formatOptionValue($entity, $referencing_uuids)] = array(
      'label' => $this->formatOptionLabel($entity, $level),
      'type' => $entity->getEntityType()->getLabel(),
      'bundle' => $entity->bundle(),
    );
  }

  /**
   * Build the options of the table select recursively.
   *
   * @param array $options
   *   The array of options for the tableselect form type element.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to create the option on.
   * @param string $referencing_uuids
   *   The referencing uuids. Format: uuid1___uuid2___...
   * @param int $level
   *   The number of indentation div.
   */
  protected function buildEntityOptions(array &$options, EntityInterface $entity, $referencing_uuids = '', $level = 0) {
    if ($level > $this::MAX_REFERENCED_ENTITIES_DEPTH) {
      return;
    }
    // The option for the current entity.
    $this->addEntityOption($options, $entity, $referencing_uuids, $level);

    // Build the options for the referenced entities.
    foreach ($this->getReplicableReferencedEntities($entity) as $referenced_entity) {
      $this->buildEntityOptions($options, $referenced_entity, $referencing_uuids . $entity->uuid() . $this::VALUE_SEPARATOR, $level + 1);
    }
  }

}
