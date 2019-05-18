<?php

namespace Drupal\onehub\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\onehub\OneHubUpdater;

/**
 * Class OneHubUpdateForm.
 */
class OneHubUpdateForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'update_one_hub_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $desc = t('<p>Use the form to update the OneHub Drupal File table with OneHub 
    itself.</p><p>Useful if you have documents that are not attached to entities.
    This will also run via cron as well.</p>');
    $form['message'] = [
      '#type' => 'item',
      '#markup' => $desc,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Update OneHub Tables'),
    ];

    return $form;
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
    $return = (new OneHubUpdater())->updateOneHub();
  }

}
