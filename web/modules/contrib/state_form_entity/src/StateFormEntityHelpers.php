<?php

namespace Drupal\state_form_entity;


/**
 * Class StateHelpers.
 *
 * @package Drupal\state_form_entity
 */
class StateFormEntityHelpers {

  /**
   * @var
   */
  protected $entity_type_manager;

  /**
   * The following states may be applied to an element:
   */
  const STATE_ELEMENTS = [
    'enabled' => 'enabled',
    'disabled' => 'disabled',
    'required' => 'required',
    'optional' => 'optional',
    'visible' => 'visible',
    'invisible' => 'invisible',
    'checked' => 'checked',
    'unchecked' => 'unchecked',
    'expanded' => 'expanded',
    'collapsed' => 'collapsed',
  ];

  const STATE_ELEMENTSS = [
    'enabled',
    'disabled',
    'required',
    'optional',
    'visible',
    'invisible',
    'checked',
    'unchecked',
    'expanded',
    'collapsed',
  ];

  /**
   * The following states may be used in remote conditions:
   */
  const STATE_REMOTE = [
    'empty' => 'empty',
    'filled' => 'filled',
    'checked' => 'checked',
    'unchecked' => 'unchecked',
    'expanded' => 'expanded',
    'collapsed' => 'collapsed',
    'value' => 'value',
  ];

  /**
   * The following states exist for both elements and remote conditions, but are
   * not fully implemented and may not change anything on the element:
   */
  const STATE_PROBABLY_USELESS = [
    'relevant' => 'relevant',
    'irrelevant' => 'irrelevant',
    'valid' => 'valid',
    'invalid' => 'invalid',
    'touched' => 'touched',
    'untouched' => 'untouched',
    'readwrite' => 'readwrite',
    'readonly' => 'readonly',
  ];

  /**
   * @param $selected
   * @return mixed
   */
  public static function getStateList($selected) {
    return constant("self::$selected");
  }

}
