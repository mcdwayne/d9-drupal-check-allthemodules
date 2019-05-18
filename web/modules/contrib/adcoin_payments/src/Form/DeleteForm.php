<?php
/**
 * The "Are you sure you want to delete this payment?" form.
 * @author appels
 */

namespace Drupal\adcoin_payments\Form;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Url;
use Drupal\Core\Render\Element;


class DeleteForm extends ConfirmFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'delete_form';
  }

  public $payment_id;

  public function getQuestion() {
    return t('Are you sure you want to delete this payment?');
  }

  public function getCancelUrl() {
    return new Url('adcoin_payments.payment_table');
  }

  public function getDescription() {
    return t('Only do this if you are sure!');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Delete it!');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $payment_id = NULL) {
    $this->payment_id = $payment_id;
    return parent::buildForm($form, $form_state);
  }

  /**
    * {@inheritdoc}
    */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $query = \Drupal::database();
    $query->delete('adcoin_payments')
      ->condition('payment_id', $this->payment_id)
      ->execute();
    drupal_set_message(t('Succesfully deleted'));
    $form_state->setRedirect('adcoin_payments.payment_table');
  }
}