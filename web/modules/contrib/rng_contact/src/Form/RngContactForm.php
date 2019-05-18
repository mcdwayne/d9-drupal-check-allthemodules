<?php

namespace Drupal\rng_contact\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\rng_contact\Entity\RngContactInterface;

/**
 * Form controller for contacts.
 */
class RngContactForm extends ContentEntityForm {

  /**
   * The entity.
   *
   * @var \Drupal\rng_contact\Entity\RngContactInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state, RngContactInterface $contact = NULL) {
    $contact = $this->entity;

    if (!$contact->isNew()) {
      $form['#title'] = $this->t('Edit contact %label', ['%label' => $contact->label()]);
    }

    return parent::form($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $contact = $this->entity;
    $is_new = $contact->isNew();
    $contact->save();

    $t_args = ['%label' => $contact->label()];
    if ($is_new) {
      drupal_set_message(t('Contact %label has been created.', $t_args));
    }
    else {
      drupal_set_message(t('Contact %label was updated.', $t_args));
    }

    if (!$contact->isNew() && $contact->access('view')) {
      $form_state->setRedirect('entity.rng_contact.canonical', ['rng_contact' => $contact->id()]);
    }
    else {
      $form_state->setRedirect('<front>');
    }
  }

}
