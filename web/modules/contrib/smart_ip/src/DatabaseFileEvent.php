<?php

/**
 * @file
 * Contains \Drupal\smart_ip\DatabaseFileEvent.
 */

namespace Drupal\smart_ip;

use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides Smart IP database file related events for event listeners.
 *
 * @package Drupal\smart_ip
 */
class DatabaseFileEvent extends Event {

  /**
   * Contains array of configuration names that will be editable.
   *
   * @var array
   */
  protected $editableConfigNames;

  /**
   * Contains Smart IP admin settings $form.
   *
   * @var array
   */
  protected $form;

  /**
   * Contains Smart IP admin settings $form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Get form.
   *
   * @return array
   *   An associative array containing the structure of the form.
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Set form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   */
  public function setForm(array $form) {
    $this->form = $form;
  }

  /**
   * Get form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The current state of the form.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Set form state.
   *
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   */
  public function setFormState(FormStateInterface $formState) {
    $this->formState = $formState;
  }

  /**
   * Get the config names.
   *
   * @return array
   *   Config names.
   */
  public function getEditableConfigNames() {
    return $this->editableConfigNames;
  }

  /**
   * Set the config names.
   *
   * @param array $editableConfigNames
   *   Config names.
   */
  public function setEditableConfigNames(array $editableConfigNames) {
    $this->editableConfigNames = $editableConfigNames;
  }

}
