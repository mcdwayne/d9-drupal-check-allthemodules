<?php

namespace Drupal\affiliates_connect\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Affiliates Product edit forms.
 *
 * @ingroup affiliates_connect
 */
class AffiliatesProductForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label Affiliates Product.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label Affiliates Product.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.affiliates_product.canonical', ['affiliates_product' => $entity->id()]);
  }

}
