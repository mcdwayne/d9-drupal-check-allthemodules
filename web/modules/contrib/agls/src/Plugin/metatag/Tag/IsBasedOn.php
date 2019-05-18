<?php

namespace Drupal\agls\Plugin\metatag\Tag;

use Drupal\metatag\Plugin\metatag\Tag\MetaNameBase;

/**
 * The AGLS IsBasedOn tag.
 *
 * @MetatagTag(
 *   id = "agls_isbasedon",
 *   label = @Translation("Is Based On"),
 *   description = @Translation("A related resource of which the described resource is a performance, production, derivation, translation or interpretation."),
 *   name = "AGLSTERMS.isBasedOn",
 *   group = "agls",
 *   weight = 9,
 *   type = "label",
 *   secure = FALSE,
 *   multiple = FALSE
 * )
 */
class IsBasedOn extends MetaNameBase {
  // Inherits everything from Base.
}
