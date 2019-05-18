<?php

namespace Drupal\entity_reference_layout\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when erl attributes are merged into layout_options.
 */
class ErlMergeAttributesEvent extends Event {

  const EVENT_NAME = 'erl_merge_attributes';

  public $attributes;
  public $formValues;

  /**
   * Constructs the object.
   *
   * @param array $attributes
   *   Attributes array being built.
   * @param array $form_values
   *   Form values to merge into attributes.
   */
  public function __construct(array &$attributes, array $form_values) {
    $this->attributes =& $attributes;
    $this->formValues = $form_values;
  }

}
