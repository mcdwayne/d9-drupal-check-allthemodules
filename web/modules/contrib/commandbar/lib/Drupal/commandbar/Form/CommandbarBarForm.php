<?php

/**
 * @file
 * Contains \Drupal\commandbar\Form\CommandbarBarForm.
 */

namespace Drupal\commandbar\Form;

use Drupal\Core\Form\FormInterface;

/**
 * Provides a form object for the commandbar search form.
 */
class CommandbarBarForm implements FormInterface {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'commandbar_bar_form';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {

     $form['command'] = array(
      '#type' => 'textfield',
      '#maxlength' => 250,
      '#autocomplete_path' => 'commandbar/autocomplete',
      '#placeholder' => t('Jump to page'),
      '#weight' => -1,
      '#attributes' => array(
        'class' => array(
          'input-search',
        ),
      ),
      '#attached' => array(
        'library' => array(
          array('commandbar', 'drupal.commandbar'),
        ),
      ),
    );

    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Go'),
      '#attributes' => array(
        'class' => array(
          'search',
        ),
      ),
    );

    return $form;
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {}

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $continue = TRUE;
    $string = $form_state['values']['command'];
    drupal_alter('commandbar_submit', $string, $continue);
    if ($continue) {
      // Unset the destination, or else we'll end up stuck on non-existent pages.
      unset($_GET['destination']);
      drupal_goto($form_state['values']['command']);
    }
  }

}