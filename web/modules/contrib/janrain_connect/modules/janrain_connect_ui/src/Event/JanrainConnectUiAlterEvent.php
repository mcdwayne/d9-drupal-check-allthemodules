<?php

namespace Drupal\janrain_connect_ui\Event;

use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\Event;

/**
 * Janrain Connect Submit Event.
 */
class JanrainConnectUiAlterEvent extends Event {

  /**
   * Form Id.
   *
   * @var string
   */
  protected $formId;

  /**
   * Data.
   *
   * @var array
   */
  protected $data;

  /**
   * Form.
   *
   * @var array
   */
  protected $form;

  /**
   * Form State.
   *
   * @var array
   */
  protected $formState;

  /**
   * If user needs to merge accounts.
   *
   * @var \Drupal\Core\Url
   */
  protected $redirect;

  /**
   * Constructs an event object.
   *
   * @param string $form_id
   *   Form ID to be submitted.
   * @param array $data
   *   Form Data.
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormState $form_state
   *   Form State.
   */
  public function __construct($form_id, array $data, array &$form, FormState &$form_state) {
    $this->formId = $form_id;
    $this->data = $data;
    $this->form = $form;
    $this->formState = $form_state;
    $this->redirect = FALSE;
  }

  /**
   * Get the Form Id.
   */
  public function getFormId() {
    return $this->formId;
  }

  /**
   * Get the Form Id.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Get the Form.
   */
  public function getForm() {
    return $this->form;
  }

  /**
   * Get the Merge.
   */
  public function getRedirect() {
    return $this->redirect;
  }

  /**
   * Set the Form.
   */
  public function setForm($form) {
    $this->form = $form;
  }

  /**
   * Get the Form State.
   */
  public function getFormState() {
    return $this->formState;
  }

  /**
   * Set the Form State.
   */
  public function setFormState(FormState $formState) {
    $this->form_state = $formState;
  }

  /**
   * Set the Form State.
   */
  public function setRedirect(Url $redirect) {
    $this->redirect = $redirect;
  }

}
