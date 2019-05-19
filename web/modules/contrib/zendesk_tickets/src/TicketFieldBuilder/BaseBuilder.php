<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides a base field element builder.
 */
class BaseBuilder {
  use StringTranslationTrait;

  /**
   * The Zendesk field object.
   *
   * @var object
   */
  protected $field;

  /**
   * Construct a ticket field builder.
   */
  public function __construct($field) {
    $this->field = $field;
  }

  /**
   * Builds the field into a Drupal form element.
   *
   * @return array
   *   A Drupal form element.
   */
  public function getElement() {
    if (empty($this->field)) {
      return [];
    }

    $element = [];
    $field = $this->field;

    // Store the ticket field to allow any form customizations.
    $element['#ticket_field'] = $field;

    // Title.
    if (!empty($field->title_in_portal)) {
      $element['#title'] = $field->title_in_portal;
    }
    elseif (!empty($field->title)) {
      $element['#title'] = $field->title;
    }

    // Required.
    if (isset($field->required_in_portal)) {
      $element['#required'] = !empty($field->required_in_portal);
    }

    // Weight.
    if (isset($field->position)) {
      $element['#weight'] = (int) $field->position;
    }

    // Description.
    if (!empty($field->description)) {
      $element['#description'] = $field->description;
    }

    return $element;
  }

}
