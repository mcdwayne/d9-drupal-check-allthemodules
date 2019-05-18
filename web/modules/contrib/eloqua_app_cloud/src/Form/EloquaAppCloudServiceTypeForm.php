<?php

namespace Drupal\eloqua_app_cloud\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class EloquaAppCloudServiceTypeForm.
 *
 * @package Drupal\eloqua_app_cloud\Form
 */
class EloquaAppCloudServiceTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $eloqua_app_cloud_service_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $eloqua_app_cloud_service_type->label(),
      '#description' => $this->t("Label for the Eloqua AppCloud Service type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $eloqua_app_cloud_service_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\eloqua_app_cloud\Entity\EloquaAppCloudServiceType::load',
      ],
      '#disabled' => !$eloqua_app_cloud_service_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $eloqua_app_cloud_service_type = $this->entity;
    $status = $eloqua_app_cloud_service_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Eloqua AppCloud Service type.', [
          '%label' => $eloqua_app_cloud_service_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Eloqua AppCloud Service type.', [
          '%label' => $eloqua_app_cloud_service_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($eloqua_app_cloud_service_type->urlInfo('collection'));
  }

}
