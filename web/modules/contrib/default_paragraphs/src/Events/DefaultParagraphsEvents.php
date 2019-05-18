<?php

namespace Drupal\default_paragraphs\Events;

/**
 * Contains events triggered by default paragraphs widget.
 */
final class DefaultParagraphsEvents {

  /**
   * Occurs when default paragraph entity is added to the widget.
   *
   * It allows other modules to modify the paragraph entity that is being
   * added as default.
   *
   * @var string
   */
  const ADDED = 'default_paragraphs.added';

}
