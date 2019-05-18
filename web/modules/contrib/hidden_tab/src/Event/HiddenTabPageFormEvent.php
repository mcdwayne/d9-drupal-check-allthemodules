<?php

namespace Drupal\hidden_tab\Event;

use Drupal\Core\Form\FormStateInterface;
use Drupal\hidden_tab\Entity\HiddenTabPageInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * The event published when a new Hidden Tab Page is being created.
 *
 * So that other modules may add their own elements to the form. Has 3 phase
 * of form creation, validation and save.
 */
class HiddenTabPageFormEvent extends Event {

  /**
   * Name of this event.
   */
  const EVENT_NAME = 'HIDDEN_TAB_PAGE_FORM_EVENT';

  /**
   * If the event is for when the edit/add form is being created.
   */
  const PHASE_FORM = 0;

  /**
   * If the event is for when the edit/add form is being validated.
   */
  const PHASE_VALIDATE = 1;

  /**
   * If the event is for when the edit/add form is being saved.
   */
  const PHASE_SUBMIT = 2;

  /**
   * The generated form for entity creation/edit.
   *
   * @var array
   */
  public $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  public $formState;

  /**
   * From the constants in the class, which phase this event belongs to.
   *
   * That is, form creation, validation and save.
   *
   * @var int
   *
   * @see \Drupal\hidden_tab\Event\HiddenTabPageFormEvent::PHASE_FORM
   * @see \Drupal\hidden_tab\Event\HiddenTabPageFormEvent::PHASE_VALIDATE
   * @see \Drupal\hidden_tab\Event\HiddenTabPageFormEvent::PHASE_SUBMIT
   */
  public $phase;

  /**
   * The page entity being created (might not have been saved yet).
   *
   * @var \Drupal\hidden_tab\Entity\HiddenTabPageInterface
   */
  public $page;

  /**
   * HiddenTabPageFormEvent constructor.
   *
   * @param array $form
   *   See $this->form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   See $this->formState.
   * @param \Drupal\hidden_tab\Entity\HiddenTabPageInterface $page
   *   See $this->page.
   * @param int $phase
   *   See $this->phase.
   */
  public function __construct(array &$form,
                              FormStateInterface $form_state,
                              HiddenTabPageInterface $page,
                              int $phase) {
    $this->form = &$form;
    $this->formState = $form_state;
    $this->phase = $phase;
    $this->page = $page;
  }

  /**
   * Are we editing an excising page or creating a new one.
   *
   * @return bool
   *   True if in editing phase.
   */
  public function isEdit(): bool {
    return !$this->page->isNew();
  }

  /**
   * True if form state has a value for given config (shortcut method).
   *
   * @param string $prefix
   *   Config prefix, prefix of the caller.
   * @param string $name
   *   The config name.
   *
   * @return bool
   *   If form state has value for given name.
   */
  public function has(string $prefix, string $name): bool {
    return $this->formState->hasValue($prefix . $name);
  }

  /**
   * Get a single value from form state or all of them (shortcut method).
   *
   * @param string $prefix
   *   String prefixed to $name, so that does not collide with other form
   *   values.
   * @param string|NULL $name
   *   Name of the value to get or NULL if all values is desired.
   *
   * @return array|mixed
   *   Value in form state, or all values if name is not given.
   */
  public function get(string $prefix = NULL, string $name = NULL) {
    if ($name === NULL) {
      assert($prefix === NULL);
      return $this->formState->getValues();
    }
    elseif ($name) {
      assert($prefix !== NULL);
    }
    return $this->formState->getValue($prefix . $name);
  }

  /**
   * Set value on form state (shortcut method).
   *
   * @param string $prefix
   *   String prefixed to $name, so that does not collide with other form
   *   values.
   * @param string $name
   *   Name of the value.
   * @param $value
   *   The actual value.
   *
   * @return \Drupal\hidden_tab\Event\HiddenTabPageFormEvent
   *   This.
   */
  public function set(string $prefix, string $name, $value): HiddenTabPageFormEvent {
    $this->formState->setValue($prefix . $name, $value);
    return $this;
  }

  /**
   * Set an error on form state (shortcut method).
   *
   * @param string $message
   *   The message to set.
   * @param string $prefix
   *   Element name prefix, used by the caller.
   * @param string $name
   *   Name of the element to set error on.
   *
   * @return \Drupal\hidden_tab\Event\HiddenTabPageFormEvent
   *   This.
   */
  public function error(string $message, string $prefix = '', string $name = ''): HiddenTabPageFormEvent {
    $this->formState->setErrorByName($prefix . $name, $message);
    return $this;
  }

}
