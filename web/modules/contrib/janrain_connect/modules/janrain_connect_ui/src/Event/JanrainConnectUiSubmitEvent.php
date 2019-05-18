<?php

namespace Drupal\janrain_connect_ui\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Janrain Connect Submit Event.
 */
class JanrainConnectUiSubmitEvent extends Event {

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
   * Constructs an event object.
   *
   * @param string $form_id
   *   Form ID to be submitted.
   * @param array $data
   *   Form Data.
   * @param mixed $form
   *   Form.
   * @param mixed $form_state
   *   Form State.
   */
  public function __construct($form_id, array $data, $form, $form_state) {
    $this->formId = $form_id;
    $this->data = $data;
    $this->form = $form;
    $this->formState = $form_state;
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
   * Get the Form State.
   */
  public function getFormState() {
    return $this->formState;
  }

}
