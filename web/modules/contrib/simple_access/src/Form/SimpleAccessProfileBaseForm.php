<?php

namespace Drupal\simple_access\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Profile base form.
 */
class SimpleAccessProfileBaseForm extends EntityForm {

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

    /** @var \Drupal\simple_access\Entity\SimpleAccessProfile $profile */
    $profile = $this->entity;

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#default_value' => $profile->label(),
      '#size' => 40,
      '#maxlength' => 80,
      '#description' => $this->t('The name for the access group as it will appear on the content editing form.'),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $profile->id(),
      '#machine_name' => [
        'exists' => [$this, 'exist'],
      ],
      '#disabled' => !$profile->isNew(),
    ];

    $form['access'] = [
      '#type' => 'simple_access_groups',
      '#default_value' => $profile->access,
      '#override_privilege' => TRUE,
    ];

    $form['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#default_value' => $profile->weight,
      '#delta' => 10,
      '#description' => $this->t('When setting permissions, heavier names will sink and lighter names will be positioned nearer the top.'),
    ];

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\simple_access\Entity\SimpleAccessProfile $profile */
    $profile = $this->entity;

    $status = $profile->save();

    if ($status) {
      $this->messenger()->addMessage($this->t('Saved the %label access profile.', [
        '%label' => $profile->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label access profile was not saved.', [
        '%label' => $profile->label(),
      ]));
    }

    $form_state->setRedirect('entity.simple_access.admin_profiles');
  }

}
