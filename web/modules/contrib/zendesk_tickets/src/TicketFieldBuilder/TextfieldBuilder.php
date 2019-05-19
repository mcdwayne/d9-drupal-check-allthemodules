<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides a textfield field element builder.
 */
class TextfieldBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'textfield';

    return $element;
  }

}
