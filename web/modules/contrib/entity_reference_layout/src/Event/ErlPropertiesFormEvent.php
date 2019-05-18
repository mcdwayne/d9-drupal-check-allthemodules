<?php

namespace Drupal\entity_reference_layout\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a layout options form is built.
 */
class ErlPropertiesFormEvent extends Event {

  const EVENT_NAME = 'erl_properties_form';

  public $form;
  public $formDefaults;

  /**
   * Constructs the object.
   *
   * @param array $form
   *   The form array being built.
   * @param array $form_defaults
   *   Default values to populate form.
   */
  public function __construct(array &$form, array $form_defaults) {
    $this->form =& $form;
    $this->formDefaults = $form_defaults;
  }

}
