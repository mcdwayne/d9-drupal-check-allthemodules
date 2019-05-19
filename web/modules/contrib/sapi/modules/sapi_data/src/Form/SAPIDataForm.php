<?php

namespace Drupal\sapi_data\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Statistics API Data entry edit forms.
 *
 * @ingroup sapi_data
 */
class SAPIDataForm extends ContentEntityForm {
  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\sapi_data\Entity\SAPIData */
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
        drupal_set_message($this->t('Created the %label Statistics API Data entry.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Statistics API Data entry.', [
          '%label' => $entity->label(),
        ]));
    }

    if (is_null($form_state->getRedirect())) {
      $form_state->setRedirect('entity.sapi_data.canonical', ['sapi_data' => $entity->id()]);
    }
  }

}
