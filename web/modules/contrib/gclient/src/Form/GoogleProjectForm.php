<?php

namespace Drupal\gclient\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GoogleProjectForm extends EntityForm {

  /**
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $entity = $this->entity;
    $entity_type = $entity->getEntityType();
    $entity_label = $entity_type->getLabel();
    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add ' . $entity_label);
    }
    else {
      $form['#title'] = $this->t('Edit %label ' . $entity_label, ['%label' => $entity->label()]);
    }

    $form['label'] = [
      '#title' => t('Label'),
      '#type' => 'textfield',
      '#default_value' => $entity->label(),
      '#description' => $this->t('The human-readable name of this entity.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $entity->id(),
      '#maxlength' => EntityTypeInterface::ID_MAX_LENGTH,
      '#disabled' => !$entity->isNew(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
      '#description' => $this->t('A unique machine-readable name for this entity. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['project_id'] = [
      '#title' => t('Project ID'),
      '#type' => 'textfield',
      '#default_value' => $entity->get('project_id'),
      '#description' => $this->t('The project ID.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    $form['project_number'] = [
      '#title' => t('Project number'),
      '#type' => 'textfield',
      '#default_value' => $entity->get('project_number'),
      '#description' => $this->t('The project number.'),
      '#required' => TRUE,
      '#size' => 30,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $entity_type = $entity->getEntityType();
    $entity_label = $entity_type->getLabel();
    // $entity->set('id', trim($entity->id()));
    // $entity->set('label', trim($entity->label()));
    $status = $entity->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message($this->t($entity_label . ' %label has been updated.', ['%label' => $entity->label()]));
    }
    else {
      drupal_set_message($this->t($entity_label . ' %label has been created.', ['%label' => $entity->label()]));
    }

    $form_state->setRedirect('entity.' . $entity_type->id() . '.collection');
  }

  /**
   * Check whether the entity type exists.
   *
   * @param $id
   * @return bool
   */
  public function exists($id) {
    return !empty($this->entityTypeManager->getStorage($this->entity->getEntityType()->id())->load($id));
  }

}
