<?php

namespace Drupal\dcat\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class DcatVcardTypeForm.
 *
 * @package Drupal\dcat\Form
 */
class DcatVcardTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $dcat_vcard_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $dcat_vcard_type->label(),
      '#description' => $this->t("Label for the vCard type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $dcat_vcard_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\dcat\Entity\DcatVcardType::load',
      ],
      '#disabled' => !$dcat_vcard_type->isNew(),
    ];

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $dcat_vcard_type = $this->entity;
    $status = $dcat_vcard_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label vCard type.', [
          '%label' => $dcat_vcard_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label vCard type.', [
          '%label' => $dcat_vcard_type->label(),
        ]));
    }
    $form_state->setRedirectUrl($dcat_vcard_type->toUrl('collection'));
  }

}
