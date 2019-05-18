<?php

namespace Drupal\panels_extended\Event;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event fired on the Extended Panels Content form.
 */
class ExtendedPanelsContentFormEvent extends Event {

  /**
   * Event name for form alter.
   */
  const FORM_ALTER = 'panels_extended.epcf_alter';

  /**
   * Event name for form validation.
   */
  const FORM_VALIDATE = 'panels_extended.epcf_validate';

  /**
   * Event name for form submission.
   */
  const FORM_SUBMIT = 'panels_extended.epcf_submit';

  /**
   * An associative array containing the structure of the form.
   *
   * @var array
   */
  protected $form;

  /**
   * The current state of the form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * Constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function __construct(array &$form, FormStateInterface $form_state) {
    $this->form = &$form;
    $this->formState = $form_state;
  }

  /**
   * Gets the form as reference.
   *
   * @return array
   *   Reference to the form.
   */
  public function &getForm() {
    return $this->form;
  }

  /**
   * Gets the current state of the form.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The current state of the form.
   */
  public function getFormState() {
    return $this->formState;
  }

}
