<?php

namespace Drupal\trash\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\trash\TrashManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a entity restore confirmation form.
 */
class RestoreForm extends ConfirmFormBase {

  /**
   * The content entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * The entity manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Trash Manager service.
   *
   * @var \Drupal\trash\TrashManagerInterface
   */
  protected $trashManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('trash.manager')
    );
  }

  /**
   * Constructs a new RestoreForm.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\trash\TrashManagerInterface $trash_manager
   *   The Trash Manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TrashManagerInterface $trash_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->trashManager = $trash_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'restore_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to restore "@label"?', ['@label' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('The @entity "@label" will be restored.', [
      '@entity' => $this->entity->getEntityType()
        ->get('label'),
      '@label' => $this->entity->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Restore');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('trash.default', ['entity_type_id' => $this->entity->getEntityTypeId()]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $entity_id = NULL) {
    if (!empty($entity_type_id)) {
      /** @var \Drupal\Core\Entity\EntityStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity_type_id);
      if (!$this->entity = $storage->load($entity_id)) {
        throw new NotFoundHttpException(t('Deleted entity with ID @id was not found.', ['@id' => $entity_id]));
      }
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->trashManager->restore($this->entity);
    drupal_set_message(t('The @entity "@label" has been restored.', [
      '@entity' => $this->entity->getEntityType()
        ->get('label'),
      '@label' => $this->entity->label(),
    ]));
    $form_state->setRedirect('trash.default', ['entity_type_id' => $this->entity->getEntityTypeId()]);
  }

}
