<?php

namespace Drupal\headline_group;

use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines an interface for the link field item.
 */
interface HeadlineGroupItemInterface extends FieldItemInterface {

  /**
   * Specifies that blank headlines are left blank.
   */
  const HG_BLANK = 'headline_is_blank';

  /**
   * Specifies that blank headlines are replaced by the entity title if available.
   */
  const HG_OVERRIDE = 'headline_is_flexible';

  /**
   * Specifies that headlines always inherit the entity title.
   */
  const HG_PROHIBIT = 'headline_is_title';

  /**
   * Allow adding a superhead.
   */
  const HG_SUPERHEAD = 'include_superhead';

  /**
   * Allow adding a subhead.
   */
  const HG_SUBHEAD = 'include_subhead';

}
