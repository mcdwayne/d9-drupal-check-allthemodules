<?php

namespace Drupal\dibs\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Dibs transaction edit forms.
 *
 * @ingroup dibs
 */
class DibsTransactionForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\dibs\Entity\DibsTransaction */
    $form = parent::form($form, $form_state);
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
        drupal_set_message($this->t('Created the %label Dibs transaction.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Dibs transaction.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.dibs_transaction.canonical', ['dibs_transaction' => $entity->id()]);
  }

}
