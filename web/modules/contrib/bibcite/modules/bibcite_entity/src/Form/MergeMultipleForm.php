<?php

namespace Drupal\bibcite_entity\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Merge multiple bibliographic entities into one.
 */
class MergeMultipleForm extends ConfirmFormBase {

  /**
   * The array of entities to delete.
   *
   * @var array
   */
  protected $entityInfo = [];

  /**
   * The tempstore object.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The field name for filtering.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Constructs a DeleteMultiple form object.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user object.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityTypeManagerInterface $manager, AccountInterface $current_user) {
    $this->tempStore = $temp_store_factory->get('bibcite_entity_merge_multiple');
    $this->entityTypeManager = $manager;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'bibcite_entity_merge_multiple';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to merge these @entity_type_label?', [
      '@entity_type_label' => $this->entityType->getPluralLabel(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Merge');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url("entity.{$this->entityType->id()}.collection");
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $field_name = NULL) {
    $this->entityType = $this->entityTypeManager->getDefinition($entity_type_id);
    $this->entityInfo = $this->tempStore->get($this->currentUser->id());
    $this->fieldName = $field_name;

    $form['entities'] = [
      '#theme' => 'item_list',
      '#items' => $this->entityInfo,
    ];

    $form['target'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Select target'),
      '#description' => $this->t('@entity_type_label to be merged into.', [
        '@entity_type_label' => $this->entityType->getLabel(),
      ]),
      '#target_type' => $this->entityType->id(),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (isset($this->entityInfo[$form_state->getValue('target')])) {
      $form_state->setErrorByName('target', $this->t('@label cannot be merged into oneself', ['@label' => $this->entityInfo[$form_state->getValue('target')]]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $target_id = $form_state->getValue('target');

    $operations = [];
    foreach ($this->entityInfo as $id => $label) {
      $operations[] = [
        'bibcite_entity_merge_entity', [
          $id,
          $target_id,
          $this->entityType->id(),
          $this->fieldName,
        ],
      ];
      $operations[] = [
        'bibcite_entity_merge_entity_delete', [
          $id,
          $this->entityType->id(),
          $this->fieldName,
        ],
      ];
    }

    $batch = [
      'title' => t('Merging'),
      'operations' => $operations,
      'finished' => 'bibcite_entity_merge_entity_finished',
      'file' => drupal_get_path('module', 'bibcite_entity') . '/bibcite_entity.batch.inc',
    ];

    batch_set($batch);

    $this->tempStore->delete($this->currentUser->id());
  }

}
