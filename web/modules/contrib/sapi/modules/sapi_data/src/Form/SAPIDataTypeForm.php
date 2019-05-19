<?php

namespace Drupal\sapi_data\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SAPIDataTypeForm.
 *
 * @package Drupal\sapi_data\Form
 */
class SAPIDataTypeForm extends EntityForm {
  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $sapi_data_type = $this->entity;
    $form['label'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $sapi_data_type->label(),
      '#description' => $this->t("Label for the Statistics API Data entry type."),
      '#required' => TRUE,
    );

    $form['id'] = array(
      '#type' => 'machine_name',
      '#default_value' => $sapi_data_type->id(),
      '#machine_name' => array(
        'exists' => '\Drupal\sapi_data\Entity\SAPIDataType::load',
      ),
      '#disabled' => !$sapi_data_type->isNew(),
    );

    /* You will need additional form elements for your custom properties. */

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $sapi_data_type = $this->entity;
    $status = $sapi_data_type->save();

    switch ($status) {
      case SAVED_NEW:
        drupal_set_message($this->t('Created the %label Statistics API Data entry type.', [
          '%label' => $sapi_data_type->label(),
        ]));
        break;

      default:
        drupal_set_message($this->t('Saved the %label Statistics API Data entry type.', [
          '%label' => $sapi_data_type->label(),
        ]));
    }

    if (is_null($form_state->getRedirect())) {
      $form_state->setRedirectUrl($sapi_data_type->urlInfo('collection'));
    }
  }

}
