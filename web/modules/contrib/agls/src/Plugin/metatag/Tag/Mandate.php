<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Mandate tag.
 *
 * @MetatagTag(
 *   id = "agls_mandate",
 *   label = @Translation("Mandate"),
 *   description = @Translation("A specific legal instrument which requires or drives the creation or provision of the resource. The property is useful to indicate the specific legal instrument which requires the resource being described to be created or provided. The value of this property may a text string describing a specific Act, Regulation or Case, or a URI pointing to the legal instrument in question"),
 *   name = "AGLSTERMS.mandate",
 *   group = "agls",
 *   weight = 11,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Mandate extends MetaNameBase {
  // Inherits everything from Base.
}
