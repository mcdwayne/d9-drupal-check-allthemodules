<?php

namespace Drupal\commerce_installments\Form;

use Drupal\commerce_installments\UrlParameterBuilderTrait;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Installment edit forms.
 *
 * @ingroup commerce_installments
 */
class InstallmentForm extends ContentEntityForm {

  use UrlParameterBuilderTrait;

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = &$this->entity;
    $entity->setNewRevision(FALSE);

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Installment.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Installment.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.installment.canonical', ['installment' => $entity->id()] + $this->getUrlParameters());
  }

}
