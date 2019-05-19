<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides a textarea field element builder.
 */
class TextareaBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'textarea';

    return $element;
  }

}
