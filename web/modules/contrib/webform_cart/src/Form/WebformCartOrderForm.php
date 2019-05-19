<?php

namespace Drupal\webform_cart\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Webform cart order entity edit forms.
 *
 * @ingroup webform_cart
 */
class WebformCartOrderForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\webform_cart\Entity\WebformCartOrder */
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
        drupal_set_message($this->t('Created the %label Webform cart order entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Webform cart order entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.webform_cart_order.canonical', ['webform_cart_order' => $entity->id()]);
  }

}
