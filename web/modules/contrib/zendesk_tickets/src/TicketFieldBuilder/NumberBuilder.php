<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides a number field element builder.
 */
class NumberBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'number';

    return $element;
  }

}
