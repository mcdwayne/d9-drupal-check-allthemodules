<?php

namespace Drupal\measuremail\Form;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\measuremail\MeasuremailInterface;


/**
 * Form for deleting an measuremail element.
 *
 * @internal
 */
class MeasuremailElementDeleteForm extends ConfirmFormBase {

  /**
   * The measuremail element containing the measuremail form to be deleted.
   *
   * @var \Drupal\measuremail\MeasuremailInterface
   */
  protected $measuremail;

  /**
   * The measuremail element to be deleted.
   *
   * @var \Drupal\measuremail\MeasuremailElementsInterface
   */
  protected $measuremailElement;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @element element from the %form measuremail form?', [
      '%form' => $this->measuremail->label(),
      '@element' => $this->measuremailElement->label(),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public function getCancelUrl() {
    return $this->measuremail->toUrl('edit-form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'measuremail_element_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, MeasuremailInterface $measuremail = NULL, $measuremail_element = NULL) {
    $this->measuremail = $measuremail;
    $this->measuremailElement = $this->measuremail->getElement($measuremail_element);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $this->measuremail->deleteMeasuremailElement($this->measuremailElement);
    } catch (EntityStorageException $e) {
      drupal_set_message($this->t('An error has occurred.'));
    }
    drupal_set_message($this->t('The measuremail element %name has been deleted.', ['%name' => $this->measuremailElement->label()]));
    $form_state->setRedirectUrl($this->measuremail->toUrl('edit-form'));
  }

}
