<?php

namespace Drupal\sitename_by_path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sitename_by_path\SitenameByPathStorage;

/**
 * Simple form to add an entry, with all the interesting fields.
 */
class SitenameByPathAddForm extends FormBase {

  /**
   * Set form id.
   */
  public function getFormId() {
    return 'sbp_add_form';
  }

  /**
   * Build add item form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['message'] = [
      '#markup' => 'Add a Sitename By Path entry.',
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#size' => 60,
      '#required' => TRUE,
      '#description' => $this->t('Specify pages by using their paths. The "*" character is a wildcard. Do not include first "/".'),
    ];
    $form['sitename'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Sitename'),
      '#size' => 60,
      '#required' => TRUE,
      '#description' => $this->t('Specify "system.site.name".'),
    ];
    $form['frontpage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Frontpage URL'),
      '#size' => 60,
      '#required' => TRUE,
      '#description' => $this->t('Specify "system.site.page.front" URL. Include or exclude first "/" depending on your theme.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add'),
    ];
    return $form;
  }

  /**
   * Validate form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Submit form actions.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the submitted entry.
    $entry = [
      'path' => '/' . $form_state->getValue('path'),
      'sitename' => $form_state->getValue('sitename'),
      'frontpage' => $form_state->getValue('frontpage'),
    ];
    $return = SitenameByPathStorage::insert($entry);
    if ($return) {
      drupal_set_message($this->t('Sitename By Path: Item Created.'));
      $form_state->setRedirect('sbp_list');
    }
  }

}
