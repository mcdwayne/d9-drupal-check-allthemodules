<?php

namespace Drupal\eloqua_app_cloud\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Eloqua AppCloud Service edit forms.
 *
 * @ingroup eloqua_app_cloud
 */
class EloquaAppCloudServiceForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var $entity \Drupal\eloqua_app_cloud\Entity\EloquaAppCloudService */
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
        drupal_set_message($this->t('Created the %label Eloqua AppCloud Service.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Eloqua AppCloud Service.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.eloqua_app_cloud_service.canonical', ['eloqua_app_cloud_service' => $entity->id()]);
  }

}
