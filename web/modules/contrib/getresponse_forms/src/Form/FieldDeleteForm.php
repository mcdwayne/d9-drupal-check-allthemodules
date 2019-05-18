<?php

namespace Drupal\getresponse_forms\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\getresponse_forms\GetresponseFormsInterface;

/**
 * Form for deleting a field.
 */
class FieldDeleteForm extends ConfirmFormBase {

  /**
   * The form containing the field to be deleted.
   *
   * @var \Drupal\getresponse_forms\GetresponseFormsInterface
   */
  protected $getresponseForm;

  /**
   * The GetResponse custom field to be deleted.
   *
   * @var \Drupal\getresponse_forms\FieldInterface
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove the @field field from the %form form?', ['%form' => $this->getresponseForm->label(), '@field' => $this->field->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getresponseForm->urlInfo('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'getresponse_forms_field_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, GetresponseFormsInterface $getresponse_forms = NULL, $field = NULL) {
    $this->getresponseForm = $getresponse_forms;
    $this->field = $this->getresponseForm->getField($field);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->getresponseForm->deleteField($this->field);
    drupal_set_message($this->t('The field %name has been removed.', ['%name' => $this->field->label()]));
    $form_state->setRedirectUrl($this->getresponseForm->urlInfo('edit-form'));
  }

}
