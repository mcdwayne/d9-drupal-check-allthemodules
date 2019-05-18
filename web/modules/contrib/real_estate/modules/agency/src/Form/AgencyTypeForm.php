<?php

namespace Drupal\real_estate_agency\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class AgencyTypeForm.
 *
 * @package Drupal\real_estate_agency\Form
 */
class AgencyTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $real_estate_agency_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $real_estate_agency_type->label(),
      '#description' => $this->t("Label for the Agency type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $real_estate_agency_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\real_estate_agency\Entity\AgencyType::load',
      ],
      '#disabled' => !$real_estate_agency_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $real_estate_agency_type = $this->entity;
    $status = $real_estate_agency_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Agency type.', [
          '%label' => $real_estate_agency_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Agency type.', [
          '%label' => $real_estate_agency_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($real_estate_agency_type->toUrl('collection'));
  }

}
