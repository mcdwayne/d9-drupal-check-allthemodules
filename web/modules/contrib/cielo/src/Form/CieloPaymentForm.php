<?php

namespace Drupal\cielo\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Cielo credit card payment edit forms.
 *
 * @ingroup cielo
 */
class CieloPaymentForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\cielo\Entity\CieloPayment */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Cielo credit card payment.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Cielo credit card payment.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.cielo_payment.canonical', ['cielo_payment' => $entity->id()]);
  }

}
