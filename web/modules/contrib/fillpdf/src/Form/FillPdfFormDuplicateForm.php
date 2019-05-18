<?php

namespace Drupal\fillpdf\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Form controller for the FillPdfForm duplicate form.
 *
 * @internal
 */
class FillPdfFormDuplicateForm extends FillPdfFormConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $label = trim($this->getEntity()->label()) ?: $this->t('unnamed');
    return $this->t('Create duplicate of %label?', ['%label' => $label]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Save');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $label = trim($this->getEntity()->label()) ?: $this->t('unnamed');

    $form['new_admin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administrative title'),
      '#required' => TRUE,
      '#size' => 32,
      '#maxlength' => 255,
      '#default_value' => $this->t('Duplicate of @label', ['@label' => $label]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fillpdf_form = $this->getEntity();

    $new_form = $fillpdf_form->createDuplicate();
    $new_form->set('admin_title', $form_state->getValue('new_admin_title'));
    $status = $new_form->save();

    if ($status === SAVED_NEW) {
      $form_fields = $fillpdf_form->getFormFields();
      foreach ($form_fields as $fillpdf_form_field) {
        $duplicate_field = $fillpdf_form_field->createDuplicate();
        $duplicate_field->set('fillpdf_form', $new_form->id());
        $duplicate_field->save();
      }

      $this->getLogger('fillpdf')->notice('Duplicated FillPDF form %original_id to %new_id.', [
        '%original_id' => $fillpdf_form->id(),
        '%new_id' => $new_form->id(),
      ]);
      $this->messenger()->addStatus($this->t('FillPDF form has been duplicated.'));

      return new RedirectResponse($new_form->toUrl()->toString());
    }
  }

}
