<?php

namespace Drupal\entity_delete_op\Form;

use Drupal\entity_delete_op\DeleteManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use League\Container\Exception\NotFoundException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RestoreForm extends ConfirmFormBase {

  protected $entityTypeManager;

  protected $deleteManager;

  protected $entity;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, DeleteManagerInterface $delete_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->deleteManager = $delete_manager;
    $this->config = $this->config('entity_delete_op.settings');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_delete_op.manager')
    );
  }

  public function getFormId() {
    return 'entity_delete_op.restore_form';
  }

  public function getConfirmText() {
    return $this->t('Restore');
  }

  public function getCancelUrl() {
    return new Url('<front>');
  }

  public function getQuestion() {
    $action_label = $this->config->get('restore_label') ?? 'restore';
    return $this->t('Are you sure you want to @action_label "@label"?', [
      '@action_label' => $this->t($action_label),
      '@label' => $this->entity->label(),
    ]);
  }

  public function getDescription() {
    return $this->t('This action restores the entity to a non-@deleted_label state.', [
      '@deleted_label' => $this->config->get('delete_label_past') ?? 'deleted',
    ]);
  }

  public function buildForm(array $form, FormStateInterface $form_state, $entity_type_id = NULL, $entity_id = NULL) {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $this->entity = $storage->load($entity_id);

    if (empty($this->entity)) {
      throw new NotFoundException($this->t('The entity with ID @id was not found.', ['@id' => $entity_id]));
    }

    if (!$this->entity->getEntityType()->get('entity_delete_op')) {
      throw new NotFoundException($this->t('The entity with ID @id is not supported.', ['@id' => $entity_id]));
    }

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->deleteManager->restore($this->entity);
    $action_label = $this->config->get('restore_label_past') ?? 'restored';
    $this->messenger()->addMessage($this->t('The entity "%label" has been @action_label.', [
      '%label' => $this->entity->label(),
      '@action_label' => $this->t($action_label),
    ]));
    $form_state->setRedirect('<front>');
  }

}