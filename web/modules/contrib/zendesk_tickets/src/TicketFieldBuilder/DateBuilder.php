<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides a date field element builder.
 */
class DateBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'date';

    return $element;
  }

}
