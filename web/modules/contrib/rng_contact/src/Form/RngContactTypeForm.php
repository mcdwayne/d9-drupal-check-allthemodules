<?php

namespace Drupal\rng_contact\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng_contact\Entity\RngContactType;
use Drupal\rng_contact\Entity\RngContactTypeInterface;

/**
 * Form controller for contact types.
 */
class RngContactTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $rng_contact_type = $this->entity;

    if (!$rng_contact_type->isNew()) {
      $form['#title'] = $this->t('Edit registrant type %label', [
        '%label' => $rng_contact_type->label(),
      ]);
    }

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $rng_contact_type->label(),
      '#required' => TRUE,
    ];
    $form['id'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Machine name'),
      '#default_value' => $rng_contact_type->id(),
      '#machine_name' => [
        'exists' => [$this, 'exists'],
      ],
    ];

    $form['description'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $rng_contact_type->description,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($entity_id) {
    return RngContactType::load($entity_id) instanceof RngContactTypeInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $rng_contact_type = $this->getEntity();
    $status = $rng_contact_type->save();

    $t_args = ['%label' => $rng_contact_type->label()];

    if ($status === SAVED_NEW) {
      $this->logger('rng')->notice('%label contact type was added.', $t_args);
      drupal_set_message($this->t('%label contact type was added.', $t_args));
    }
    else {
      $this->logger('rng')->notice('%label contact type has been updated.', $t_args);
      drupal_set_message($this->t('%label contact type has been updated.', $t_args));
    }

    $form_state->setRedirect('entity.rng_contact_type.collection');
  }

}
