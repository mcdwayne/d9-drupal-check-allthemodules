<?php
declare(strict_types=1);

namespace Drupal\membership_entity\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MembershipEntityTypeForm.
 */
class MembershipTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $type = $this->entity;

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add membership type');
    }
    else {
      $form['#title'] = $this->t('Edit %label membership type', ['%label' => $type->label()]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $type->label(),
      '#description' => $this->t('The label for this membership type. This text will be displayed as part of the list of available membership options on the <em>join</em> page. This name must be unique.'),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#maxlength' => EntityTypeInterface::BUNDLE_MAX_LENGTH,
      '#default_value' => $type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\membership_entity\Entity\MembershipEntityType::load',
      ],
      '#disabled' => !$type->isNew(),
      '#description' => $this->t('A unique machine-readable name for this membership type. It must only contain lowercase letters, numbers, and underscores.'),
    ];

    $form['description'] = [
      '#title' => t('Description'),
      '#type' => 'textarea',
      '#default_value' => $type->description(),
      '#description' => t('This text will be displayed on the <em>join</em> page.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $type = $this->entity;
    $status = $type->save();

    $params = ['%label' => $type->label()];
    $messages = [
      SAVED_NEW => $this->t('Created the %label Membership type.', $params),
      'default' => $this->t('Saved the %label Membership type.', $params),
    ];
    if (isset($messages[$status])) {
      $this->messenger()->addMessage($messages[$status]);
    }
    else {
      $this->messenger()->addMessage($messages['default']);
    }

    $form_state->setRedirectUrl($type->toUrl('collection'));
  }
}
