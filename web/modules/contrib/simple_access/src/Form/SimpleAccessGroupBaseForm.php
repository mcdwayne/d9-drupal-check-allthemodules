<?php

namespace Drupal\simple_access\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides group base form.
 */
class SimpleAccessGroupBaseForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
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

    $group = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $group->label(),
      '#size' => 40,
      '#maxlength' => 80,
      '#description' => $this->t('The name for the access group as it will appear on the content editing form.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $group->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$group->isNew(),
    ];

    if ($group->id() != 'owner') {
      $form['roles'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Roles'),
        '#default_value' => $group->roles,
        '#options' => user_role_names(),
        '#description' => $this->t('Roles that can view'),
      ];
      $form['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#default_value' => $group->weight,
        '#delta' => 10,
        '#description' => $this->t('When setting permissions, heavier names will sink and lighter names will be positioned nearer the top.'),
      ];
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $group = $this->entity;

    $status = $group->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label access group.', [
        '%label' => $group->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label access group was not saved.', [
        '%label' => $group->label(),
      ]));
    }

    $form_state->setRedirect('entity.simple_access.admin_groups');
  }

}
