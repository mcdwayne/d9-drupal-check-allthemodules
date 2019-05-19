<?php

namespace Drupal\teamleader_contact;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface for TeamleaderContact service.
 */
interface TeamleaderContactInterface {

  /**
   * Add contact data to Teamleader.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function addContactToTeamleader(array $form, FormStateInterface &$form_state);

}
