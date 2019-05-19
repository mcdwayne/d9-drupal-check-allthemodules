<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides an email field element builder.
 */
class EmailBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'email';

    return $element;
  }

}
