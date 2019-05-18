<?php

namespace Drupal\chatbot_api_entities\Form;

use Drupal\chatbot_api_entities\Plugin\PushHandlerManager;
use Drupal\chatbot_api_entities\Plugin\QueryHandlerManager;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityCollectionForm.
 */
class EntityCollectionForm extends EntityForm {

  /**
   * Entity bundle info.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $entityTypeBundleInfo;

  /**
   * Entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Push handler manager.
   *
   * @var \Drupal\chatbot_api_entities\Plugin\PushHandlerManager
   */
  protected $pushHandlerManager;

  /**
   * Query handler manager.
   *
   * @var \Drupal\chatbot_api_entities\Plugin\QueryHandlerManager
   */
  protected $queryHandlerManager;

  /**
   * Entity being edited.
   *
   * @var \Drupal\chatbot_api_entities\Entity\EntityCollectionInterface
   */
  protected $entity;

  /**
   * Constructs a new EntityCollectionForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entityTypeBundleInfo
   *   Entity bundle info.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Field manager.
   * @param \Drupal\chatbot_api_entities\Plugin\PushHandlerManager $pushHandlerManager
   *   Push handler.
   * @param \Drupal\chatbot_api_entities\Plugin\QueryHandlerManager $queryHandlerManager
   *   Query handler.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $entityTypeBundleInfo, EntityFieldManagerInterface $entityFieldManager, PushHandlerManager $pushHandlerManager, QueryHandlerManager $queryHandlerManager) {
    $this->entityTypeManager = $entityTypeManager;
    $this->entityTypeBundleInfo = $entityTypeBundleInfo;
    $this->entityFieldManager = $entityFieldManager;
    $this->pushHandlerManager = $pushHandlerManager;
    $this->queryHandlerManager = $queryHandlerManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.chatbot_api_entities_push_handler'),
      $container->get('plugin.manager.chatbot_api_entities_query_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $collection = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $collection->label(),
      '#description' => $this->t("Label for the Entity collection."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $collection->id(),
      '#machine_name' => [
        'exists' => '\Drupal\chatbot_api_entities\Entity\EntityCollection::load',
      ],
      '#disabled' => !$collection->isNew(),
    ];

    $entityTypes = $this->entityTypeManager->getDefinitions();
    $options = [];
    foreach ($entityTypes as $id => $entityType) {
      if (!$entityType->entityClassImplements(ContentEntityInterface::class)) {
        continue;
      }
      foreach ($this->entityTypeBundleInfo->getBundleInfo($id) as $bundleId => $bundle) {
        $options[(string) $entityType->getLabel()]["$id:$bundleId"] = $bundle['label'];
      }
    }
    $form['entity_type'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['container-inline'],
      ],
    ];
    $default_entity_type = $this->getEntityTypeForCollection($form_state);
    $form['entity_type']['entity_type'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Entity Type'),
      '#description' => $this->t('Choose the entity type for the entities to comprise this collection. Creating and update entities of this time will queue the collection to be sent to the remote endpoint during the next cron run.'),
      '#default_value' => $default_entity_type,
      '#ajax' => [
        'wrapper' => 'edit-configuration-container',
        'callback' => '::changeEntityTypeCallback',
        'trigger_as' => [
          'name' => 'op',
          'value' => $this->t('Change Entity type'),
        ],
      ],
    ];
    $form['entity_type']['change'] = [
      '#limit_validation_errors' => [['entity_type']],
      '#type' => 'submit',
      '#value' => $this->t('Change Entity type'),
      '#attributes' => [
        'class' => ['js-hide'],
      ],
      '#submit' => ['::changeEntityType'],
      '#ajax' => [
        'callback' => '::changeEntityTypeCallback',
        'wrapper' => 'edit-configuration-container',
      ],
    ];

    $fields = [];
    $form['configuration'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'edit-configuration-container',
      ],
    ];
    if ($default_entity_type) {
      list ($entity_type, $bundle) = explode(':', $default_entity_type, 2);
      $fieldsDefinitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
      foreach ($fieldsDefinitions as $name => $field) {
        if ($field->getType() !== 'string') {
          continue;
        }
        if ($field->getName() === $this->entityTypeManager->getDefinition($entity_type)->getKey('label')) {
          // Bypass the title/label fields.
          continue;
        }
        $fields[$name] = $field->getLabel();
      }
      if ($fields) {
        $form['configuration']['synonyms'] = [
          '#type' => 'select',
          '#empty_option' => $this->t('None'),
          '#empty_values' => '',
          '#options' => $fields,
          '#title' => $this->t('Synonyms field'),
          '#description' => $this->t('Select the field to use for synonyms'),
          '#default_value' => $collection->getSynonymField(),
        ];
      }
      else {
        $form['configuration']['synonyms'] = [
          '#type' => 'markup',
          '#markup' => $this->t('There are no text fields for this entity type. Add an <em>Text (plain)</em> field to enable synonyms support.'),
        ];
      }
      $query_handlers = $this->queryHandlerManager->getDefinitions();
      $query_options = [];
      $query_config = $collection->get('query_handlers');
      foreach ($query_handlers as $id => $query_handler) {
        $instance = $this->queryHandlerManager->createInstance($id, isset($query_config[$id]) ? $query_config[$id] : []);
        if ($instance->applies($entity_type)) {
          $query_options[$id] = $query_handler['label'];
        }
      }
      $form['configuration']['enabled_query_handlers'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Enabled query handlers'),
        '#options' => $query_options,
        '#required' => TRUE,
        '#default_value' => array_keys($collection->get('query_handlers')),
      ];
    }
    $push_handlers = $this->pushHandlerManager->getDefinitions();
    $push_options = [];
    $push_config = $collection->get('push_handlers');
    $form['enabled_push_handlers'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled push handlers'),
      '#required' => TRUE,
      '#default_value' => array_keys($collection->get('push_handlers')),
    ];
    foreach ($push_handlers as $id => $push_handler) {
      $instance = $this->pushHandlerManager->createInstance($id, isset($push_config[$id]) ? $push_config[$id] : []);
      $push_options[$id] = $push_handler['label'];
      if ($push_form = $instance->getSettingsForm($collection, $form, $form_state)) {
        $form += [
          'push_handler_configuration' => [
            '#type' => 'vertical_tabs',
            '#title' => $this->t('Push handler configuration'),
          ],
        ];
        $form['push_handlers']['settings'][$id] = [
          '#type' => 'details',
          '#tree' => TRUE,
          '#open' => TRUE,
          '#title' => $push_handler['label'],
          '#group' => 'push_handler_configuration',
          '#parents' => ['push_handler_configuration', $id, 'settings'],
        ] + $push_form;
      }
    }
    $form['enabled_push_handlers']['#options'] = $push_options;
    return $form;
  }

  /**
   * Submit handler.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function changeEntityType(array $form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return array
   *   Form elements.
   */
  public function changeEntityTypeCallback(array $form, FormStateInterface $form_state) {
    if (!isset($form['configuration'])) {
      return [];
    }
    return $form['configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $chatbot_api_entities_collection = $this->entity;
    $status = $chatbot_api_entities_collection->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label collection. The collection has been queued for sending during the next cron run.', [
          '%label' => $chatbot_api_entities_collection->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label collection. The collection has been queued for sending during the next cron run.', [
          '%label' => $chatbot_api_entities_collection->label(),
        ]));
    }
    $form_state->setRedirectUrl($chatbot_api_entities_collection->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = parent::buildEntity($form, $form_state);
    list ($entity_type, $bundle) = explode(':', $form_state->getValue('entity_type'), 2);
    $entity->set('entity_type', $entity_type);
    $entity->set('bundle', $bundle);
    // Reset these to empty.
    $entity->set('queryHandlers', []);
    $entity->set('pushHandlers', []);
    foreach (array_filter($form_state->getValue('enabled_query_handlers') ?: []) as $query_handler) {
      $entity->setQueryHandlerConfiguration($query_handler, ['id' => $query_handler]);
    }
    foreach (array_filter($form_state->getValue('enabled_push_handlers') ?: []) as $push_handler) {
      $entity->setPushHandlerConfiguration($push_handler, [
        'id' => $push_handler,
      ] + $form_state->getValue(['push_handler_configuration', $push_handler]));
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    $push_handlers = $this->pushHandlerManager->getDefinitions();
    $push_config = $this->entity->get('pushHandlers');
    $enabled = array_filter($form_state->getValue('enabled_push_handlers'));
    foreach ($push_handlers as $id => $push_handler) {
      if (empty($enabled[$id])) {
        continue;
      }
      $instance = $this->pushHandlerManager->createInstance($id, isset($push_config[$id]) ? $push_config[$id] : []);
      $instance->validateSettingsForm($this->entity, $form, $form_state);
    }
  }

  /**
   * Gets the entity type being edited.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return string
   *   Entity type.
   */
  protected function getEntityTypeForCollection(FormStateInterface $form_state) {
    $default_entity_type = $form_state->getValue('entity_type');
    if (!$default_entity_type && $this->entity->getCollectionEntityTypeId()) {
      $default_entity_type = $this->entity->getCollectionEntityTypeId() . ':' . $this->entity->getCollectionBundle();
    }
    return $default_entity_type;
  }

}
