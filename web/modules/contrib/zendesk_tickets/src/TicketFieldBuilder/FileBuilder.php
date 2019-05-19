<?php

namespace Drupal\zendesk_tickets\TicketFieldBuilder;

/**
 * Provides a file field element builder.
 */
class FileBuilder extends BaseBuilder {

  /**
   * {@inheritdoc}
   */
  public function getElement() {
    $element = parent::getElement();
    if (empty($element)) {
      return [];
    }

    $element['#type'] = 'file';

    return $element;
  }

}
