<?php

namespace Drupal\sitename_by_path\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\sitename_by_path\SitenameByPathStorage;

/**
 * Update entry form.
 */
class SitenameByPathUpdateForm extends FormBase {

  /**
   * Set form id.
   */
  public function getFormId() {
    return 'sbp_update_form';
  }

  /**
   * Build update item form.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $sbp_id = NULL) {

    // Find item to display.
    $entries = SitenameByPathStorage::load();
    foreach ($entries as $entry) {
      if ($entry->id == $sbp_id) {
        $default_entry = $entry;
      }
    }

    // Add some explanatory text to the form.
    $form['message'] = [
      '#markup' => $this->t('Update Sitename By Path entry.'),
    ];
    $form['id'] = [
      '#type'  => 'hidden',
      '#title' => $this->t('Entry ID'),
      '#default_value' => $sbp_id,
    ];
    $form['path'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Path'),
      '#size'  => 60,
      '#default_value' => substr($default_entry->path, 1),
      '#description' => $this->t('Specify pages by using their paths. The "*" character is a wildcard. Do not include first "/".'),
    ];
    $form['sitename'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Sitename'),
      '#size'  => 60,
      '#default_value' => $default_entry->sitename,
      '#description' => $this->t('Specify "system.site.name".'),
    ];
    $form['frontpage'] = [
      '#type'  => 'textfield',
      '#title' => $this->t('Frontpage URL'),
      '#size'  => 60,
      '#default_value' => $default_entry->frontpage,
      '#description'   => $this->t('Specify "system.site.page.front" URL. Include or exclude first "/" depending on your theme.'),
    ];
    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Update'),
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
      'id'   => $form_state->getValue('id'),
      'path' => '/' . $form_state->getValue('path'),
      'sitename'  => $form_state->getValue('sitename'),
      'frontpage' => $form_state->getValue('frontpage'),
    ];
    SitenameByPathStorage::update($entry);
    $form_state->setRedirect('sbp_list');
    drupal_set_message($this->t('Sitename by Path: Item updated.'));
  }

}
