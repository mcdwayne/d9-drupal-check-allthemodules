<?php

namespace Drupal\swish_payment\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Swish transaction edit forms.
 *
 * @ingroup swish_payment
 */
class SwishTransactionForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\swish_payment\Entity\SwishTransaction */
    $form = parent::buildForm($form, $form_state);

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Swish transaction.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Swish transaction.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.swish_transaction.canonical', ['swish_transaction' => $entity->id()]);
  }

}
