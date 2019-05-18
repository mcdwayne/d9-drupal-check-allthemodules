<?php

namespace Drupal\civilcomments\Plugin\Field\FieldType;

/**
 * Interface definition for Civil Comment items.
 */
interface CivilCommentItemInterface {

  /**
   * Comments for this entity are disabled.
   */
  const DISABLED = 0;

  /**
   * Comments for this entity are closed.
   */
  const CLOSED = 1;

  /**
   * Comments for this entity are open.
   */
  const OPEN = 2;

}
