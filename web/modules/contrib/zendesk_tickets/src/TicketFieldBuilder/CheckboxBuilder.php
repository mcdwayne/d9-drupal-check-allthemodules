<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides a checkbox field element builder.
 */
class CheckboxBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'checkbox';

    // TODO: $this->field->tag should be the value to set when checked, but
    // tested checkbox fields had tag: "".
    // See https://developer.zendesk.com/rest_api/docs/core/ticket_fields.
    return $element;
  }

}
