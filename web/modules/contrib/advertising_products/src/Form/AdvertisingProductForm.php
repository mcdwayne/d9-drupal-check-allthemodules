<?php

namespace Drupal\advertising_products\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Advertising Product edit forms.
 *
 * @ingroup advertising_products
 */
class AdvertisingProductForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Advertising Product.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Advertising Product.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.advertising_product.canonical', ['advertising_product' => $entity->id()]);
  }

}
