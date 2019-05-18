<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS Jurisdiction tag.
 *
 * @MetatagTag(
 *   id = "agls_jurisdiction",
 *   label = @Translation("Jurisdiction"),
 *   description = @Translation("The name of the political/administrative entity covered by the described resource."),
 *   name = "AGLSTERMS.jurisdiction",
 *   group = "agls",
 *   weight = 10,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class Jurisdiction extends MetaNameBase {
  // Inherits everything from Base.
}
