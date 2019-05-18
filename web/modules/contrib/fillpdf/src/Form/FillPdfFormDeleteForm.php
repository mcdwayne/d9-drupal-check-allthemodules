<?php

namespace Drupal\fillpdf\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Provides a deletion confirmation form for a FillPdfForm.
 *
 * @internal
 */
class FillPdfFormDeleteForm extends FillPdfFormConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete %name?', ['%name' => $this->entity->label()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl('canonical');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $fillpdf_form = $this->getEntity();

    /** @var \Drupal\file\FileInterface $file */
    $file = File::load($fillpdf_form->get('file')->first()->target_id);
    $fillpdf_form->delete();

    $this->messenger()->addStatus($this->t('FillPDF form deleted.'));

    $form_state->setRedirect('fillpdf.forms_admin');
  }

}
