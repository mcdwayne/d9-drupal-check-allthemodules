<?php

namespace Drupal\real_estate_agency\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Agency edit forms.
 *
 * @ingroup real_estate_agency
 */
class AgencyForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\real_estate_agency\Entity\Agency */
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
        drupal_set_message($this->t('Created the %label Agency.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Agency.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.real_estate_agency.canonical', ['real_estate_agency' => $entity->id()]);
  }

}
