<?php

namespace Drupal\mattermost_integration\Form;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for configuring an Outgoing Webhook Entity.
 *
 * @package Drupal\mattermost_integration\Form
 */
class OutgoingWebhookForm extends EntityForm {

  /**
   * The entity field manager.
   *
   * @var EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var $entity \Drupal\mattermost_integration\Entity\OutgoingWebhook */
    $entity = $this->entity;
    $entity_manager = $this->entityTypeManager;

    // Create an array with all the content types of the Drupal site.
    $content_types = [];
    foreach ($entity_manager
      ->getStorage('node_type')
      ->loadMultiple() as $content_type) {
      $content_types[$content_type->id()] = $content_type->label();
    }

    // Create an array with all the available comment types.
    $comment_types = [];
    foreach ($entity_manager
      ->getStorage('comment_type')
      ->loadMultiple() as $comment_type) {
      $comment_types[$comment_type->id()] = $comment_type->label();
    }

    $available_fields = $this->getAvailableFields($entity->getContentType());
    $comment_fields = $available_fields ? $available_fields : [];

    // The label will the ID for this as well in the form of a machine_name.
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Label'),
      '#description' => $this->t('The label for this entity.'),
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => '\Drupal\mattermost_integration\Entity\OutgoingWebhook::load',
      ],
      '#default_value' => $entity->id(),
    ];
    $form['channel_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Channel ID'),
      '#description' => $this->t('The ID of the origin channel.'),
      '#required' => TRUE,
      '#default_value' => $entity->getChannelId(),
    ];
    $form['webhook_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webhook token'),
      '#description' => $this->t('The authentication token supplied by Mattermost when a webhook is created.'),
      '#required' => TRUE,
      '#default_value' => $entity->getWebhookToken(),
    ];
    $form['content_type_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Content type'),
      '#collapsible' => FALSE,
    ];
    $form['content_type_fieldset']['content_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Content type'),
      '#description' => $this->t('Specify which type of content this integration should create.'),
      '#required' => TRUE,
      '#options' => $content_types,
      '#default_value' => $entity->getContentType(),
      '#ajax' => [
        'callback' => [$this, 'updateCommentField'],
        'wrapper' => 'comment-field-wrapper',
      ],
    ];
    $form['content_type_fieldset']['wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'comment-field-wrapper'],
    ];
    $form['content_type_fieldset']['wrapper']['comment_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment field'),
      '#description' => $this->t('The field on the target content type the comments should be attached to.'),
      '#required' => TRUE,
      '#options' => $comment_fields,
      '#default_value' => $entity->getCommentField(),
    ];
    $form['comment_type_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Comment type'),
      '#collapsible' => FALSE,
    ];
    $form['comment_type_fieldset']['comment_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Comment type'),
      '#description' => $this->t('Specify which type of comment this integration should create.'),
      '#required' => TRUE,
      '#options' => $comment_types,
      '#default_value' => $entity->getCommentType(),
    ];
    $form['convert_markdown'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Convert markdown'),
      '#description' => $this->t('Convert markdown to HTML using the Parsedown library'),
      '#required' => FALSE,
      '#default_value' => $entity->getConvertMarkdown(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var $entity \Drupal\mattermost_integration\Entity\OutgoingWebhook */
    $entity = $this->entity;
    $entity_status = $entity->save();
    $this->addPostIdField($entity->getContentType());

    if ($entity_status) {
      drupal_set_message($this->t('Succesfully saved the entity'));
    }
    else {
      drupal_set_message($this->t('Something went wrong saving the entity, please try again later.'), 'error');
    }

    $redirect_url = Url::fromRoute('mattermost_integration.outgoing_webhooks');
    $form_state->setRedirectUrl($redirect_url);
  }

  /**
   * Method for getting the fields for a content type.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The new form wrapper.
   *
   * @TODO: Don't select a value on update.
   * @TODO: Only get fields that are of type 'Comment'.
   */
  public function updateCommentField(array $form, FormStateInterface $form_state) {
    $content_type = $form_state->getUserInput()['content_type'];
    $fields = $this->getAvailableFields($content_type);

    $form['content_type_fieldset']['wrapper']['comment_field']['#options'] = $fields;

    return $form['content_type_fieldset']['wrapper'];
  }

  /**
   * Method for getting available fields for a content type.
   *
   * @param string $content_type
   *   The content type of which fields to get.
   *
   * @return array
   *   The available fields.
   */
  public function getAvailableFields($content_type) {
    $entity_manager = $this->entityFieldManager;

    $raw_fields = array_filter(
      $entity_manager->getFieldDefinitions('node', $content_type), function ($field_definition) {
        return $field_definition instanceof FieldConfigInterface;
      }
    );

    $fields = [];
    foreach ($raw_fields as $label => $raw_value) {
      $fields[$label] = $label;
    }

    return $fields;
  }

  /**
   * Method for adding a mattermost_integration_post_id field to a content type.
   *
   * @param string $bundle
   *   The entity type to attach this field to.
   *
   * @return bool
   *   Returns TRUE if field was created. False if it already exists.
   */
  public function addPostIdField($bundle) {
    if (isset($this->getAvailableFields($bundle)['mattermost_integration_post_id'])) {
      return FALSE;
    }

    FieldStorageConfig::create([
      'field_name' => 'mattermost_integration_post_id',
      'entity_type' => 'node',
      'type' => 'string',
      'cardinality' => '1',
    ])->save();

    FieldConfig::create([
      'field_name' => 'mattermost_integration_post_id',
      'entity_type' => 'node',
      'bundle' => $bundle,
      'label' => 'Mattermost post ID',
    ])->save();

    EntityFormDisplay::load("node.$bundle.default")
      ->setComponent('mattermost_integration_post_id', [
        'type' => 'text_textfield',
      ])
      ->save();

    EntityViewDisplay::load("node.$bundle.default")
      ->setComponent('mattermost_integration_post_id', [
        'type' => 'text_default',
      ])
      ->save();

    return TRUE;
  }

}
