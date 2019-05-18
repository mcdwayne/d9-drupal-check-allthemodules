<?php

namespace Drupal\entity_reference_crud_display\Form;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Builds and process a form for editing a single entity field.
 *
 * @internal
 */
class EntityReferenceCrudDisplayForm extends FormBase {

  /**
   * Stores the tempstore factory.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The node type storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeTypeStorage;

  /**
   * Constructs a new EditFieldForm.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Entity\EntityStorageInterface $node_type_storage
   *   The node type storage.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, EntityStorageInterface $node_type_storage) {
    $this->nodeTypeStorage = $node_type_storage;
    $this->tempStoreFactory = $temp_store_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('entity_type.manager')->getStorage('node_type')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity_reference_crud_display_form';
  }

  /**
   * {@inheritdoc}
   *
   * Builds a form for a single entity field.
   */
  public function buildForm(array $form, FormStateInterface $form_state, ContentEntityInterface $entity = NULL) {
    if (!$form_state->has('entity')) {
      $this->init($form_state, $entity);
    }

    // Add the field form.
    $form_state->get('form_display')->buildForm($entity, $form, $form_state);

    // Add a dummy changed timestamp field to attach form errors to.
    if ($entity instanceof EntityChangedInterface) {
      $form['changed_field'] = [
        '#type' => 'hidden',
        '#value' => $entity->getChangedTime(),
      ];
    }

    // Add a submit button. Give it a class for easy JavaScript targeting.
    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#attributes' => ['class' => ['use-ajax-submit']],
    ];

    // Use the non-inline form error display, because in
    // this case the errors are already near the form element.
    $form['#disable_inline_form_errors'] = TRUE;

    return $form;
  }

  /**
   * Initialize the form state and the entity before the first form build.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state interface.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   */
  protected function init(FormStateInterface $form_state, ContentEntityInterface $entity) {
    // @todo Rather than special-casing $node->revision, invoke prepareEdit()
    //   once https://www.drupal.org/node/1863258 lands.
    if ($entity->getEntityTypeId() == 'node') {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $node_type */
      $node_type = $this->nodeTypeStorage->load($entity->bundle());
      $entity->setNewRevision($node_type->isNewRevision());
      $entity->revision_log = NULL;
    }

    $form_state->set('entity', $entity);

    // Fetch the display used by the form. It is the display for the 'default'
    // form mode, with only the current field visible.
    $display = EntityFormDisplay::collectRenderDisplay($entity, 'default');

    $form_state->set('form_display', $display);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $form_state->get('form_display')->validateFormValues($entity, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   *
   * Saves the entity with updated values for the edited field.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $this->buildEntity($form, $form_state);
    $form_state->set('entity', $entity);

    // Store entity in tempstore with its UUID as tempstore key.
    $this->tempStoreFactory->get('crud_form')->set($entity->uuid(), $entity);
  }

  /**
   * Returns a cloned entity containing updated field values.
   *
   * Calling code may then validate the returned entity, and if valid, transfer
   * it back to the form state and save it.
   *
   * @param array $form
   *   The form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The entity.
   */
  protected function buildEntity(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = clone $form_state->get('entity');
    $field_name = $form_state->get('field_name');

    $form_state->get('form_display')->extractFormValues($entity, $form, $form_state);

    // @todo Refine automated log messages and abstract them to all entity
    //   types: https://www.drupal.org/node/1678002.
    if ($entity->getEntityTypeId() == 'node' && $entity->isNewRevision() && $entity->revision_log->isEmpty()) {
      $entity->revision_log = $this->t('Updated the %field-name field through in-place editing.', ['%field-name' => $entity->get($field_name)->getFieldDefinition()->getLabel()]);
    }

    return $entity;
  }

}
